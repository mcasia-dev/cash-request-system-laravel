<?php

namespace App\Services\CashRequest;

use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Models\ForLiquidation;
use App\Models\LiquidationReceipt;
use App\Services\Remarks\StatusRemarkResolver;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class ForLiquidationService
{
    /**
     * Load receipt media entries for the liquidation, with per-record caching.
     */
    public function getReceiptEntries(ForLiquidation $record): array
    {
        static $cache = [];

        if (!array_key_exists($record->id, $cache)) {
            $cache[$record->id] = LiquidationReceipt::query()
                ->where('liquidation_id', $record->id)
                ->get()
                ->flatMap(function (LiquidationReceipt $receipt) {
                    return $receipt->getMedia('liquidation-receipts')->map(fn($media) => [
                        'url' => $media->getUrl(),
                        'amount' => $receipt->receipt_amount,
                        'receipt_number' => $receipt->receipt_number,
                        'remarks' => $receipt->remarks,
                    ]);
                })
                ->filter()
                ->values()
                ->all();
        }

        return $cache[$record->id];
    }

    /**
     * Build a closure that renders receipt images and details as HTML.
     * @return \Closure
     */
    public function getReceiptImageState(): \Closure
    {
        return function (ForLiquidation $record) {
            $receipts = $this->getReceiptEntries($record);

            if (empty($receipts)) {
                return 'No receipt images uploaded.';
            }

            $html = '<div style="display:flex;flex-wrap:wrap;gap:10px;">';

            foreach ($receipts as $receipt) {
                $safeUrl = e($receipt['url']);
                $amount = number_format((float)($receipt['amount'] ?? 0), 2);
                $receiptNumber = filled($receipt['receipt_number']) ? e($receipt['receipt_number']) : 'N/A';
                $remarks = filled($receipt['remarks']) ? e($receipt['remarks']) : 'N/A';

                $html .= '<div style="width:220px;border:1px solid #e5e7eb;border-radius:8px;padding:10px;background:#fff;color:#111827;">'
                    . '<a href="'
                    . $safeUrl
                    . '" target="_blank" rel="noopener noreferrer">'
                    . '<img src="'
                    . $safeUrl
                    . '" alt="Receipt image" style="width:100%;max-height:180px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;" />'
                    . '</a>'
                    . '<div style="margin-top:8px;font-size:12px;line-height:1.45;">'
                    . '<div><strong>Amount:</strong> PHP ' . $amount . '</div>'
                    . '<div><strong>Receipt No:</strong> ' . $receiptNumber . '</div>'
                    . '<div><strong>Remarks:</strong> ' . $remarks . '</div>'
                    . '</div>'
                    . '</div>';
            }

            $html .= '</div>';

            return new HtmlString($html);
        };
    }

    public function canProcess(ForLiquidation $record): bool
    {
        return $record->cashRequest->status === Status::RELEASED->value
            && $record->cashRequest->status_remarks === StatusRemarks::LIQUIDATION_RECEIPT_SUBMITTED->value && $record->is_override;
    }

    public function canApprove($record): bool
    {
        return $record->cashRequest->status === Status::RELEASED->value
            && $record->cashRequest->status_remarks === StatusRemarks::LIQUIDATION_RECEIPT_SUBMITTED->value
            && $record->is_override
            && !$record->is_approved_by_treasury_manager;
    }

    public function liquidateRequest(ForLiquidation $record): void
    {
        $user = Auth::user();

        $record->cashRequest->update([
            'status' => Status::LIQUIDATED->value,
            'status_remarks' => StatusRemarks::LIQUIDATED->value,
            'date_liquidated' => Carbon::now(),
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record->cashRequest ?? $record)
            ->event('liquidated')
            ->withProperties([
                'request_no' => $record->cashRequest->request_no,
                'activity_name' => $record->cashRequest->activity_name,
                'requesting_amount' => $record->cashRequest->requesting_amount,
                'previous_status' => Status::RELEASED->value,
                'new_status' => Status::LIQUIDATED->value,
                'status_remarks' => StatusRemarks::LIQUIDATED->value,
            ])
            ->log("Cash request {$record->cashRequest->request_no} was liquidated by {$user->name} ({$user->position})");

        Notification::make()
            ->title('Liquidation approved.')
            ->success()
            ->send();
    }

    public function rejectLiquidation(ForLiquidation $record, array $data): void
    {
        $user = Auth::user();

        $record->update([
            'remarks' => $data['rejection_remarks'],
        ]);

        $record->cashRequest->update([
            'status' => Status::RELEASED->value,
            'status_remarks' => StatusRemarks::FOR_LIQUIDATION->value,
            'reason_for_rejection' => $data['rejection_remarks'],
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record->cashRequest ?? $record)
            ->event('rejected')
            ->withProperties([
                'request_no' => $record->cashRequest->request_no,
                'activity_name' => $record->cashRequest->activity_name,
                'requesting_amount' => $record->cashRequest->requesting_amount,
                'previous_status' => Status::RELEASED->value,
                'new_status' => Status::RELEASED->value,
                'status_remarks' => StatusRemarks::FOR_LIQUIDATION->value,
                'reason_for_rejection' => $data['rejection_remarks'],
            ])
            ->log("Liquidation receipts for cash request {$record->cashRequest->request_no} were rejected by {$user->name} ({$user->position})");

        Notification::make()
            ->title('Liquidation rejected.')
            ->success()
            ->send();
    }

    public function overrideRequest(ForLiquidation $record, array $data): void
    {
        $user = Auth::user();
        [$totalReceipts, $requestingAmount, $amountToReturn, $amountToReimburse] = $this->getLiquidationTotals($record);

        // Update the record status and save rejection reason
        $record->update([
            'is_override' => true,
            'remarks' => $data['override_remarks'] ?? $record->remarks,
            'receipt_amount' => $totalReceipts,
            'total_liquidated' => $totalReceipts,
            'missing_amount' => $amountToReturn,
            'total_change' => $amountToReimburse,
        ]);

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('override')
            ->withProperties([
                'request_no' => $record->request_no,
                'activity_name' => $record->activity_name,
                'requesting_amount' => $record->requesting_amount,
                'previous_status' => Status::PENDING->value,
                'new_status' => Status::IN_PROGRESS->value,
                'status_remarks' => $record->status_remarks,
            ])
            ->log("Cash request {$record->request_no} was override by {$user->name} ({$user->position})");

        Notification::make()
            ->title('Cash Request Override!')
            ->success()
            ->send();
    }

    public function getLiquidationTotals(ForLiquidation $record): array
    {
        $totalReceipts = (float)LiquidationReceipt::query()
            ->where('liquidation_id', $record->id)
            ->sum('receipt_amount');
        $requestingAmount = (float)($record->cashRequest?->requesting_amount ?? 0);
        $diff = round($totalReceipts - $requestingAmount, 2);

        $amountToReimburse = $diff > 0 ? $diff : 0.0;
        $amountToReturn = $diff < 0 ? abs($diff) : 0.0;

        return [$totalReceipts, $requestingAmount, $amountToReturn, $amountToReimburse, $diff];
    }

    public function canOverride(ForLiquidation $record): bool
    {
        if ($record->is_override) {
            return false;
        }

        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        try {
            return $user->hasPermissionTo('can-override-liquidation-receipt');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
            return false;
        }
    }

    public function isTreasuryManager(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->roles()
            ->where('name', 'treasury_manager')
            ->exists();
    }

    public function rejectActivity($record, array $data): void
    {
        DB::transaction(function () use ($record, $data): void {
            $record->update([
                'status' => 'rejected',
                'rejection_remarks' => $data['rejection_remarks'],
            ]);

            $cashRequest = $record->cashRequest ?? null;
            $total = $cashRequest->activityLists()
                ->where('status', '!=', 'rejected')
                ->sum('requesting_amount');

            $cashRequest->update([
                'requesting_amount' => (float)$total,
            ]);

            $hasRemainingActivities = $cashRequest->activityLists()
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhere('status', '!=', 'rejected');
                })
                ->exists();

            if (!$hasRemainingActivities) {
                $statusRemarks = app(StatusRemarkResolver::class)->rejectByPermissions(Auth::user(), 'treasury');

                $cashRequest->update([
                    'status' => Status::REJECTED->value,
                    'status_remarks' => $statusRemarks,
                    'reason_for_rejection' => $data['rejection_remarks'],
                ]);
            }
        });

        Notification::make()
            ->title('Activity rejected')
            ->success()
            ->send();
    }

    public function approveForLiquidationRequest($record): void
    {
        $user = Auth::user();

        $record->update([
            'is_approved_by_treasury_manager' => true,
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($record->cashRequest ?? $record)
            ->event('approved-for-liquidation')
            ->withProperties([
                'request_no' => $record->cashRequest->request_no,
                'activity_name' => $record->cashRequest->activity_name,
                'requesting_amount' => $record->cashRequest->requesting_amount,
                'previous_status' => $record->cashRequest->status,
                'new_status' => $record->cashRequest->status,
                'status_remarks' => $record->status_remarks,
            ])
            ->log("Cash request {$record->cashRequest->request_no} was approved for liquidation by {$user->name} ({$user->position})");

        Notification::make()
            ->title('Approved')
            ->success()
            ->send();
    }
}
