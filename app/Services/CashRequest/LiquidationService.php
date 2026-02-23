<?php

namespace App\Services\CashRequest;

use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Models\CashRequest;
use App\Models\ForLiquidation;
use App\Models\LiquidationReceipt;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LiquidationService
{
    /**
     * Persist liquidation data, receipts, and status updates for a cash request.
     *
     * @param array<string, mixed> $data
     */
    public function liquidate(CashRequest $record, array $data, User $user): void
    {
        $liquidation = null;

        DB::transaction(function () use ($record, $data, $user, &$liquidation): void {
            $previousStatus = $record->status;

            $totalReceipts = collect($data['liquidation_items'] ?? [])
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

            foreach ($data['liquidation_items'] as $item) {
                $receipt = LiquidationReceipt::create([
                    'liquidation_id' => $liquidation->id,
                    'receipt_amount' => $item['amount'],
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
