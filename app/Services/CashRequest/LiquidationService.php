<?php

namespace App\Services\CashRequest;

use App\Enums\CashRequest\StatusRemarks;
use App\Models\CashRequest\CashRequest;
use App\Models\CashRequest\ForLiquidation;
use App\Models\CashRequest\LiquidationReceipt;
use App\Models\User;
use App\Services\Ocr\OcrSpaceService;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class LiquidationService
{
    public function __construct(
        private readonly OcrSpaceService $ocrSpaceService
    )
    {
    }

    /**
     * Persist liquidation data, receipts, and status updates for a cash request.
     *
     * @param array<string, mixed> $data
     */
    public function liquidate(CashRequest $record, array $data, User $user): void
    {
        Log::info('Liquidation submit started', [
            'cash_request_id' => $record->id,
            'request_no' => $record->request_no,
            'user_id' => $user->id,
            'item_count' => count($data['liquidation_items'] ?? []),
        ]);

        $liquidationItems = $this->validateAndExtractReceiptNumbers($data['liquidation_items'] ?? []);
        $liquidation = null;

        DB::transaction(function () use ($record, $liquidationItems, $user, &$liquidation): void {
            $previousStatus = $record->status;

            $totalReceipts = collect($liquidationItems)
                ->sum(fn($item) => (float)($item['amount'] ?? 0));

            $requestingAmount = (float)$record->requesting_amount;
            $amountToReimburse = $totalReceipts > $requestingAmount
                ? $totalReceipts - $requestingAmount
                : 0.0;
            $missingAmount = $totalReceipts < $requestingAmount
                ? $requestingAmount - $totalReceipts
                : 0.0;

            $liquidation = ForLiquidation::firstOrCreate([
                'cash_request_id' => $record->id,
            ], [
                'total_liquidated' => $totalReceipts,
                'total_change' => $amountToReimburse,
                'missing_amount' => $missingAmount,
            ]);

            if (!$liquidation->wasRecentlyCreated) {
                $liquidation->update([
                    'total_change' => $amountToReimburse,
                    'missing_amount' => $missingAmount,
                    'receipt_amount' => $totalReceipts,
                ]);
            }

            foreach ($liquidationItems as $item) {
                $receipt = LiquidationReceipt::create([
                    'liquidation_id' => $liquidation->id,
                    'receipt_amount' => $item['amount'],
                    'receipt_number' => $item['receipt_number'],
                    'remarks' => $item['remarks'] ?? null,
                ]);

                if (!empty($item['receipt'])) {
                    $path = $item['receipt'];

                    $receipt
                        ->addMedia(Storage::disk('public')->path($path))
                        ->toMediaCollection('liquidation-receipts');
                }
            }

            $record->update([
                'status_remarks' => StatusRemarks::LIQUIDATION_RECEIPT_SUBMITTED->value,
            ]);

            activity()
                ->causedBy($user)
                ->performedOn($record)
                ->event('liquidated')
                ->withProperties([
                    'request_no' => $record->request_no,
                    'activity_name' => $record->activity_name,
                    'requesting_amount' => $record->requesting_amount,
                    'previous_status' => $previousStatus,
                    'new_status' => $record->status,
                    'status_remarks' => StatusRemarks::LIQUIDATION_RECEIPT_SUBMITTED->value,
                ])
                ->log("Liquidation Receipt for cash request {$record->request_no} was submitted by {$user->name}");
        });

        if ($liquidation instanceof ForLiquidation) {
            $this->notifyTreasuryTeam($record, $liquidation);
        }

        Notification::make()
            ->title('Successfully Submitted!')
            ->success()
            ->send();

        Log::info('Liquidation submit completed', [
            'cash_request_id' => $record->id,
            'request_no' => $record->request_no,
            'user_id' => $user->id,
            'liquidation_id' => $liquidation?->id,
        ]);
    }

    /**
     * Validate each receipt image through OCR and attach unique receipt numbers.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function validateAndExtractReceiptNumbers(array $items): array
    {
        $seen = [];

        foreach ($items as $index => $item) {
            $path = $this->resolveReceiptPath($item['receipt'] ?? null);

            if ($path === '') {
                Log::warning('Liquidation submit validation failed: missing receipt path', [
                    'item_index' => $index,
                ]);
                throw ValidationException::withMessages([
                    'liquidation_items' => 'Receipt image is required.',
                ]);
            }

            $absolutePath = Storage::disk('public')->path($path);

            try {
                Log::info('Liquidation submit OCR processing', [
                    'item_index' => $index,
                    'receipt_path' => $path,
                ]);
                $ocrText = $this->ocrSpaceService->extractTextFromImage($absolutePath);
            } catch (Throwable $exception) {
                Log::error('Liquidation submit OCR exception', [
                    'item_index' => $index,
                    'receipt_path' => $path,
                    'error' => $exception->getMessage(),
                ]);
                throw ValidationException::withMessages([
                    'liquidation_items' => 'Unable to read the receipt image. Please upload a clearer image.',
                ]);
            }

            $receiptNumber = $this->ocrSpaceService->extractReceiptNumber($ocrText);

            if (blank($receiptNumber)) {
                Log::warning('Liquidation submit validation failed: receipt number not detected', [
                    'item_index' => $index,
                    'receipt_path' => $path,
                ]);
                throw ValidationException::withMessages([
                    'liquidation_items' => 'Receipt number could not be detected from this image.',
                ]);
            }

            if (isset($seen[$receiptNumber])) {
                Log::warning('Liquidation submit validation failed: duplicate receipt number in same submission', [
                    'item_index' => $index,
                    'receipt_number' => $receiptNumber,
                ]);
                throw ValidationException::withMessages([
                    'liquidation_items' => 'Receipt number is already exist.',
                ]);
            }

            $exists = LiquidationReceipt::query()
                ->where('receipt_number', $receiptNumber)
                ->exists();

            if ($exists) {
                Log::warning('Liquidation submit validation failed: duplicate receipt number in database', [
                    'item_index' => $index,
                    'receipt_number' => $receiptNumber,
                ]);
                throw ValidationException::withMessages([
                    'liquidation_items' => 'Receipt number is already exist.',
                ]);
            }

            $seen[$receiptNumber] = true;
            $items[$index]['receipt_number'] = $receiptNumber;

            Log::info('Liquidation submit OCR extraction passed', [
                'item_index' => $index,
                'receipt_number' => $receiptNumber,
            ]);
        }

        return $items;
    }

    /**
     * Normalize FileUpload state into a persisted public disk path.
     */
    private function resolveReceiptPath(mixed $receiptState): string
    {
        if ($receiptState instanceof TemporaryUploadedFile) {
            return $receiptState->store('liquidation-receipts', 'public') ?: '';
        }

        if (is_array($receiptState)) {
            $first = reset($receiptState);
            return is_string($first) ? $first : '';
        }

        return is_string($receiptState) ? $receiptState : '';
    }

    /**
     * Notify treasury staff and treasury manager of liquidation submissions.
     */
    private function notifyTreasuryTeam(CashRequest $record, ForLiquidation $liquidation): void
    {
        $treasuryUsers = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['treasury_staff', 'treasury_manager', 'super_admin']);
            })
            ->get();

        if ($treasuryUsers->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Liquidation Receipts Submitted')
            ->body("Liquidation receipts were submitted for {$record->request_no}.")
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),
                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.for-liquidations.view', ['record' => $liquidation->id])),
            ])
            ->sendToDatabase($treasuryUsers);
    }
}
