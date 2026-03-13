<?php

namespace App\Services\CashRequest;

use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Jobs\CashRequest\RejectCashRequestJob;
use App\Services\Remarks\StatusRemarkResolver;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForFinanceVerificationService
{
    /**
     * Determine if the cash request is eligible for payment processing actions.
     *
     * @param mixed $record
     * @return bool
     */
    public function getStatus($record): bool
    {
        return $record->status === Status::IN_PROGRESS->value && $record->status_remarks === StatusRemarks::FOR_FINANCE_VERIFICATION->value;
    }

    /**
     * Apply finance verification approval, log activity, and notify the user.
     */
    public function approveRequest($record, array $data)
    {
        $user = Auth::user();
        $approved_remarks_by_role = app(StatusRemarkResolver::class)->approveByPermissions($user, 'finance');

        // Update the record status
        $record->update([
            'voucher_no' => $data['voucher_no'],
            'status' => Status::IN_PROGRESS->value,
            'status_remarks' => StatusRemarks::FOR_PAYMENT_PROCESSING->value,
        ]);

        // Log activity
        activity()
            ->causedBy(Auth::user())
            ->performedOn($record)
            ->event('approved')
            ->withProperties([
                'request_no' => $record->request_no,
                'activity_name' => $record->activity_name,
                'requesting_amount' => $record->requesting_amount,
                'previous_status' => Status::IN_PROGRESS->value,
                'new_status' => Status::IN_PROGRESS->value,
                'status_remarks' => $approved_remarks_by_role,
            ])
            ->log("Cash request {$record->request_no} was verified and approved by {$user->name} ({$user->position})");

        // Send an email notification
        // ApproveCashRequestJob::dispatch($record);

        Notification::make()
            ->title('Cash Request Update')
            ->body("Your cash request {$record->request_no} has been approved by Finance and forwarded for payment processing.")
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),

                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.cash-requests.track-status', ['record' => $record->id])),
            ])
            ->sendToDatabase($record->user);

        Notification::make()
            ->title('Cash Request Approved!')
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.for-verification.index');
    }

    /**
     * Apply finance verification rejection, log activity, and notify the user.
     */
    public function rejectRequest($record, array $data)
    {
        $user = Auth::user();
        $status_remarks = app(StatusRemarkResolver::class)->rejectByPermissions($user, 'finance');

        // Update the record status and save rejection reason
        $record->update([
            'status' => Status::REJECTED->value,
            'status_remarks' => $status_remarks,
            'reason_for_rejection' => $data['rejection_reason'],
        ]);

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($record)
            ->event('rejected')
            ->withProperties([
                'request_no' => $record->request_no,
                'activity_name' => $record->activity_name,
                'requesting_amount' => $record->requesting_amount,
                'previous_status' => Status::IN_PROGRESS->value,
                'new_status' => Status::REJECTED->value,
                'status_remarks' => $status_remarks,
                'reason_for_rejection' => $data['rejection_reason'],
            ])
            ->log("Cash request {$record->request_no} was rejected by {$user->name} ({$user->position})");

        // Send an email notification
        RejectCashRequestJob::dispatch($record);

        Notification::make()
            ->title('Cash Request Rejected!')
            ->success()
            ->send();

        return redirect()->route('filament.admin.resources.for-verification.index');

    }

    public function rejectActivity($record, array $data): void
    {
        DB::transaction(function () use ($record, $data): void {
            $record->update([
                'status' => 'rejected',
                'rejection_remarks' => $data['rejection_remarks'],
            ]);

            $cashRequest = $record->cashRequest;
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
                $statusRemarks = app(StatusRemarkResolver::class)->rejectByPermissions(Auth::user(), 'finance');

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
}
