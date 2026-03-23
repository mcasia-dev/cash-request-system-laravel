<?php

namespace App\Services\Reimbursement;

use App\Enums\CashRequest\Status;
use App\Jobs\Reimbursement\RejectReimbursementJob;
use App\Jobs\Reimbursement\SubmitReimbursementForApprovalJob;
use App\Models\Reimbursement\Reimbursement;
use App\Models\RevolvingFund\RequestDiscussion;
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class ForApprovalReimbursementService
{
    public function getCurrentStepConfiguration($record, User $user): ?array
    {
        return app(ReimbursementApprovalFlowService::class)->getCurrentStepConfiguration($record, $user);
    }

    public function approve($record, array $data = [])
    {
        try {
            $user = Auth::user();
            $previousStatus = $record->status;
            $result = app(ReimbursementApprovalFlowService::class)->applyApproval($record, $user, (array) ($data['step_form_data'] ?? []));

            activity()
                ->causedBy($user)
                ->performedOn($record)
                ->event('approved')
                ->withProperties([
                    'reimbursement_no' => $record->reimbursement_no,
                    'total_amount' => $record->total_amount,
                    'previous_status' => $previousStatus,
                    'new_status' => $result['status'],
                    'status_remarks' => $result['status_remarks'],
                    'approved_role_name' => $result['approved_role_name'] ?? null,
                    'step_form_data' => (array) ($data['step_form_data'] ?? []),
                ])
                ->log("Reimbursement {$record->reimbursement_no} approval step was completed by {$user->name} ({$user->position})");

            if (($result['is_final_step'] ?? false) === true) {
                $this->notifyPaymentProcessingApprovers($record->fresh());
            } else {
                SubmitReimbursementForApprovalJob::dispatch($record->id);
                $this->notifyCurrentApprovers($record->fresh());
            }

            if ($record->payee) {
                Notification::make()
                    ->title('Reimbursement Update')
                    ->body(
                        ($result['is_final_step'] ?? false)
                            ? "Your reimbursement {$record->reimbursement_no} is now for payment processing."
                            : "Your reimbursement {$record->reimbursement_no} has been approved."
                    )
                    ->actions([
                        NotificationAction::make('markAsRead')
                            ->button()
                            ->markAsRead(),
                        NotificationAction::make('view')
                            ->link()
                            ->url(route('filament.admin.resources.reimbursements.view', ['record' => $record->id])),
                    ])
                    ->sendToDatabase($record->payee);
            }

            Notification::make()
                ->title(($result['is_final_step'] ?? false) ? 'Forwarded to Payment Processing' : 'Approval step completed.')
                ->success()
                ->send();

            return redirect()->route('filament.admin.resources.for-approval-reimbursements.index');
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function reject($record, array $data)
    {
        try {
            $user = Auth::user();
            $previousStatus = $record->status;
            $result = app(ReimbursementApprovalFlowService::class)->applyRejection(
                $record,
                $user,
                $data['rejection_reason'],
                (array) ($data['step_form_data'] ?? []),
            );

            activity()
                ->causedBy($user)
                ->performedOn($record)
                ->event('rejected')
                ->withProperties([
                    'reimbursement_no' => $record->reimbursement_no,
                    'total_amount' => $record->total_amount,
                    'previous_status' => $previousStatus,
                    'new_status' => $result['status'],
                    'status_remarks' => $result['status_remarks'],
                    'reason_for_rejection' => $data['rejection_reason'],
                    'rejected_role_name' => $result['rejected_role_name'] ?? null,
                    'step_form_data' => (array) ($data['step_form_data'] ?? []),
                ])
                ->log("Reimbursement {$record->reimbursement_no} was rejected by {$user->name} ({$user->position})");

            RejectReimbursementJob::dispatch($record->fresh(['payee']));

            if ($record->payee) {
                Notification::make()
                    ->title('Reimbursement Update')
                    ->body("Your reimbursement {$record->reimbursement_no} has been rejected.")
                    ->actions([
                        NotificationAction::make('markAsRead')
                            ->button()
                            ->markAsRead(),
                        NotificationAction::make('view')
                            ->link()
                            ->url(route('filament.admin.resources.reimbursements.view', ['record' => $record->id])),
                    ])
                    ->sendToDatabase($record->payee);
            }

            Notification::make()
                ->title('Reimbursement Rejected')
                ->success()
                ->send();

            return redirect()->route('filament.admin.resources.for-approval-reimbursements.index');
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function notifyCurrentApprovers($record): void
    {
        $approvers = app(ReimbursementApprovalFlowService::class)->getCurrentPendingApprovers($record);

        if ($approvers->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('New Reimbursement For Approval')
            ->body("{$record->payee?->name} submitted {$record->reimbursement_no} for approval.")
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),
                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.for-approval-reimbursements.view', ['record' => $record->id])),
            ])
            ->sendToDatabase($approvers);
    }

    public function returnForClarification($record, array $data): void
    {
        $approver = Auth::user();
        $remarks = trim((string)($data['remarks'] ?? ''));

        if ($remarks === '') {
            Notification::make()
                ->title('Remarks are required.')
                ->danger()
                ->send();

            return;
        }

        $record->update([
            'status' => Status::IN_PROGRESS->value,
            'status_remarks' => 'Returned for Clarification',
        ]);

        $discussion = RequestDiscussion::query()->create([
            'discussable_type' => Reimbursement::class,
            'discussable_id' => $record->id,
            'sender_id' => $approver?->id,
            'recipient_id' => $record->payee_id,
            'type' => 'return',
            'remarks' => $remarks,
        ]);

        activity()
            ->causedBy($approver)
            ->performedOn($record)
            ->event('returned_for_clarification')
            ->withProperties([
                'reimbursement_id' => $record->id,
                'reimbursement_no' => $record->reimbursement_no,
                'remarks' => $remarks,
                'discussion_id' => $discussion->id,
            ])
            ->log("Reimbursement {$record->reimbursement_no} was returned for clarification by {$approver?->name} ({$approver?->position})");

        if ($record->payee) {
            Notification::make()
                ->title('Reimbursement Clarification Requested')
                ->body("Your reimbursement {$record->reimbursement_no} was returned with remarks: {$remarks}")
                ->actions([
                    NotificationAction::make('markAsRead')
                        ->button()
                        ->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.reimbursements.view', ['record' => $record->id])),
                ])
                ->sendToDatabase($record->payee);
        }

        Notification::make()
            ->title('Returned to requestor for clarification.')
            ->success()
            ->send();
    }

    public function respondToClarification($record, array $data): void
    {
        $requestor = Auth::user();
        $remarks = trim((string)($data['remarks'] ?? ''));

        if ($remarks === '') {
            Notification::make()
                ->title('Response is required.')
                ->danger()
                ->send();

            return;
        }

        $discussion = RequestDiscussion::query()->create([
            'discussable_type' => Reimbursement::class,
            'discussable_id' => $record->id,
            'sender_id' => $requestor?->id,
            'recipient_id' => null,
            'type' => 'response',
            'remarks' => $remarks,
        ]);

        activity()
            ->causedBy($requestor)
            ->performedOn($record)
            ->event('clarification_response_submitted')
            ->withProperties([
                'reimbursement_id' => $record->id,
                'reimbursement_no' => $record->reimbursement_no,
                'remarks' => $remarks,
                'discussion_id' => $discussion->id,
            ])
            ->log("Reimbursement {$record->reimbursement_no} clarification response was submitted by {$requestor?->name} ({$requestor?->position})");

        $approvers = app(ReimbursementApprovalFlowService::class)->getCurrentPendingApprovers($record);

        if ($approvers->isNotEmpty()) {
            Notification::make()
                ->title('Clarification Response Received')
                ->body("{$requestor?->name} responded to {$record->reimbursement_no}: {$remarks}")
                ->actions([
                    NotificationAction::make('markAsRead')
                        ->button()
                        ->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.for-approval-reimbursements.view', ['record' => $record->id])),
                ])
                ->sendToDatabase($approvers);
        }

        Notification::make()
            ->title('Response sent to approver(s).')
            ->success()
            ->send();
    }

    private function notifyPaymentProcessingApprovers($record): void
    {
        $approvers = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['treasury_staff', 'treasury_manager']);
            })
            ->get();

        if ($approvers->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Reimbursement For Payment Processing')
            ->body("{$record->reimbursement_no} is ready for payment processing.")
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),
                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.for-payment-processing-reimbursements.view', ['record' => $record->id])),
            ])
            ->sendToDatabase($approvers);
    }
}
