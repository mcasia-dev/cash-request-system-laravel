<?php

namespace App\Services\CashRequest;

use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Jobs\CashRequest\ApproveCashRequestJob;
use App\Jobs\CashRequest\RejectCashRequestJob;
use App\Models\User;
use App\Services\Remarks\StatusRemarkResolver;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ForApprovalRequestService
{
    /**
     * Apply an approval step, log activity, and dispatch notifications/jobs.
     */
    public function approveForApprovalRequest($record)
    {
        try {
            $user = Auth::user();
            $previousStatus = $record->status;
            $approvalResult = app(CashRequestApprovalFlowService::class)->applyApproval($record, $user);
            $approved_remarks_by_role = $approvalResult['approved_remarks_by_role'] ?? $approvalResult['status_remarks'];
            $newStatus = Status::IN_PROGRESS->value;

            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($record)
                ->event('approved')
                ->withProperties([
                    'request_no' => $record->request_no,
                    'activity_name' => $record->activity_name,
                    'requesting_amount' => $record->requesting_amount,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'status_remarks' => $approved_remarks_by_role,
                ])
                ->log("Cash request {$record->request_no} approval step was completed by {$user->name} ({$user->position})");

            if ($approvalResult['is_final_step'] === true) {
                ApproveCashRequestJob::dispatch($record->fresh());

                $newRecord = $record->fresh();

                if ($newRecord->status_remarks === StatusRemarks::FOR_PAYMENT_PROCESSING->value) {
                    $this->notifyPaymentProcessApprovers($newRecord);
                }
            }

            // Notify Users through Database Notifications
            Notification::make()
                ->title('Cash Request Update')
                ->body("Your cash request {$record->request_no} has been approved.")
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
                ->title(
                    $approvalResult['is_final_step']
                        ? (
                    $record->fresh()->status_remarks === StatusRemarks::FOR_FINANCE_VERIFICATION->value
                        ? 'Final approval completed. Sent to Finance Verification.'
                        : 'Final approval completed. Sent to Payment Processing.'
                    )
                        : 'Approval step completed.'
                )
                ->success()
                ->send();

            return redirect()->route('filament.admin.resources.for-approval-requests.index');
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Apply a rejection, log activity, and dispatch notifications/jobs.
     */
    public function rejectForApprovalRequest($record, array $data)
    {
        try {
            $user = Auth::user();
            $previousStatus = $record->status;
            $status_remarks = app(CashRequestApprovalFlowService::class)->applyRejection($record, $user, $data['rejection_reason']);
            $newStatus = Status::REJECTED->value;

            // Log activity
            activity()
                ->causedBy($user)
                ->performedOn($record)
                ->event('rejected')
                ->withProperties([
                    'request_no' => $record->request_no,
                    'activity_name' => $record->activity_name,
                    'requesting_amount' => $record->requesting_amount,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'status_remarks' => $status_remarks,
                    'reason_for_rejection' => $data['rejection_reason'],
                ])
                ->log("Cash request {$record->request_no} was rejected by {$user->name} ({$user->position})");

            // Send an email notification
            RejectCashRequestJob::dispatch($record->fresh());

            Notification::make()
                ->title('Cash Request Update')
                ->body("Your cash request {$record->request_no} has been rejected.")
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
                ->title('Cash Request Rejected!')
                ->success()
                ->send();

            return redirect()->route('filament.admin.resources.for-approval-requests.index');
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Notify treasury approvers that the request is ready for payment processing.
     */
    private function notifyPaymentProcessApprovers($record): void
    {
        $approvers = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['treasury_manager', 'treasury_supervisor']);
            })
            ->get();

        if ($approvers->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Cash Request For Payment Processing')
            ->body("{$record->request_no} is ready for payment processing.")
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),

                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.payment-processing.view', ['record' => $record->id])),
            ])
            ->sendToDatabase($approvers);
    }


    public function getRejectActivityAction(array $data, $record)
    {
        DB::transaction(function () use ($data, $record): void {
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
                $statusRemarks = app(StatusRemarkResolver::class)
                    ->rejectByPermissions(Auth::user(), 'approval');

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
