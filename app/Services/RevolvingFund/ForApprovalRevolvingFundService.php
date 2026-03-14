<?php

namespace App\Services\RevolvingFund;

use App\Enums\RevolvingFund\Status;
use App\Mail\RevolvingFund\RevolvingFundDiscussionMail;
use App\Models\RevolvingFund\ForApprovalRevolvingFund;
use App\Models\RevolvingFund\RequestDiscussion;
use App\Models\RevolvingFund\RevolvingFund;
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class ForApprovalRevolvingFundService
{
    public function approve(ForApprovalRevolvingFund $record)
    {
        try {
            $user = Auth::user();
            $previousStatus = $record->status;
            $result = app(RevolvingFundApprovalFlowService::class)->applyApproval($record, $user);

            activity()
                ->causedBy($user)
                ->performedOn($record)
                ->event('approved')
                ->withProperties([
                    'fund_code' => $record->fund_code,
                    'initial_amount' => $record->initial_amount,
                    'previous_status' => $previousStatus,
                    'new_status' => $result['status'],
                    'status_remarks' => $result['status_remarks'],
                    'approved_role_name' => $result['approved_role_name'] ?? null,
                ])
                ->log("Revolving fund {$record->fund_code} approval step was completed by {$user->name} ({$user->position})");

            if (($result['is_final_step'] ?? false) !== true) {
                $this->notifyCurrentApprovers($record->fresh());
            }

            if ($record->addedBy) {
                Notification::make()
                    ->title('Revolving Fund Update')
                    ->body(
                        ($result['is_final_step'] ?? false)
                            ? "Your revolving fund request {$record->fund_code} has been fully approved."
                            : "Your revolving fund request {$record->fund_code} has been approved by one step."
                    )
                    ->actions([
                        NotificationAction::make('markAsRead')
                            ->button()
                            ->markAsRead(),
                        NotificationAction::make('view')
                            ->link()
                            ->url(route('filament.admin.resources.revolving-funds.view', ['record' => $record->id])),
                    ])
                    ->sendToDatabase($record->addedBy);
            }

            Notification::make()
                ->title(($result['is_final_step'] ?? false) ? 'Revolving fund request fully approved.' : 'Approval step completed.')
                ->success()
                ->send();

            return redirect()->route('filament.admin.resources.for-approval-revolving-funds.index');
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function reject(ForApprovalRevolvingFund $record)
    {
        try {
            $user = Auth::user();
            $previousStatus = $record->status;
            $result = app(RevolvingFundApprovalFlowService::class)->applyRejection($record, $user);

            activity()
                ->causedBy($user)
                ->performedOn($record)
                ->event('rejected')
                ->withProperties([
                    'fund_code' => $record->fund_code,
                    'initial_amount' => $record->initial_amount,
                    'previous_status' => $previousStatus,
                    'new_status' => $result['status'],
                    'status_remarks' => $result['status_remarks'],
                    'rejected_role_name' => $result['rejected_role_name'] ?? null,
                ])
                ->log("Revolving fund {$record->fund_code} was rejected by {$user->name} ({$user->position})");

            if ($record->addedBy) {
                Notification::make()
                    ->title('Revolving Fund Update')
                    ->body("Your revolving fund request {$record->fund_code} has been rejected.")
                    ->actions([
                        NotificationAction::make('markAsRead')
                            ->button()
                            ->markAsRead(),
                        NotificationAction::make('view')
                            ->link()
                            ->url(route('filament.admin.resources.revolving-funds.view', ['record' => $record->id])),
                    ])
                    ->sendToDatabase($record->addedBy);
            }

            Notification::make()
                ->title('Revolving fund request rejected.')
                ->success()
                ->send();

            return redirect()->route('filament.admin.resources.for-approval-revolving-funds.index');
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function notifyCurrentApprovers($record, bool $isSubmission = false): void
    {
        $approvers = app(RevolvingFundApprovalFlowService::class)->getCurrentPendingApprovers($record);

        if ($approvers->isEmpty()) {
            return;
        }

        if ($isSubmission) {
            $this->addDiscussion(
                record: $record,
                senderId: $record->added_by,
                recipientId: null,
                type: 'submission',
                remarks: "Revolving fund request {$record->fund_code} was submitted for approval.",
            );
        }

        Notification::make()
            ->title('New Revolving Fund Request For Approval')
            ->body("{$record->addedBy?->name} submitted {$record->fund_code} for approval.")
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),
                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.for-approval-revolving-funds.view', ['record' => $record->id])),
            ])
            ->sendToDatabase($approvers);

        $this->sendBulkMail(
            users: $approvers,
            subject: "Revolving Fund {$record->fund_code} For Approval",
            title: 'New Revolving Fund Request',
            message: "{$record->addedBy?->name} submitted {$record->fund_code} for your approval.",
            actionUrl: route('filament.admin.resources.for-approval-revolving-funds.view', ['record' => $record->id]),
            actionLabel: 'Review Request',
        );
    }

    public function returnForClarification(ForApprovalRevolvingFund $record, array $data)
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
            'status' => $record->status === Status::PENDING->value ? Status::IN_PROGRESS->value : $record->status,
            'status_remarks' => 'Returned for Clarification',
        ]);

        $discussion = $this->addDiscussion(
            record: $record,
            senderId: $approver?->id,
            recipientId: $record->added_by,
            type: 'return',
            remarks: $remarks,
        );

        activity()
            ->causedBy($approver)
            ->performedOn($record)
            ->event('returned_for_clarification')
            ->withProperties([
                'fund_code' => $record->fund_code,
                'remarks' => $remarks,
                'discussion_id' => $discussion->id,
            ])
            ->log("Revolving fund {$record->fund_code} was returned for clarification by {$approver->name} ({$approver->position})");

        if ($record->addedBy) {
            Notification::make()
                ->title('Revolving Fund Clarification Requested')
                ->body("Your request {$record->fund_code} was returned with remarks: {$remarks}")
                ->actions([
                    NotificationAction::make('markAsRead')
                        ->button()
                        ->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.revolving-funds.view', ['record' => $record->id])),
                ])
                ->sendToDatabase($record->addedBy);

            $this->sendMail(
                user: $record->addedBy,
                subject: "Revolving Fund {$record->fund_code} Returned",
                title: 'Clarification Needed',
                message: "Your request {$record->fund_code} was returned with this remark: {$remarks}",
                actionUrl: route('filament.admin.resources.revolving-funds.view', ['record' => $record->id]),
                actionLabel: 'Respond to Clarification',
            );
        }

        Notification::make()
            ->title('Returned to requestor for clarification.')
            ->success()
            ->send();
    }

    public function respondToClarification($record, array $data)
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

        $discussion = $this->addDiscussion(
            record: $record,
            senderId: $requestor?->id,
            recipientId: null,
            type: 'response',
            remarks: $remarks,
        );

        activity()
            ->causedBy($requestor)
            ->performedOn($record)
            ->event('clarification_response_submitted')
            ->withProperties([
                'fund_code' => $record->fund_code,
                'remarks' => $remarks,
                'discussion_id' => $discussion->id,
            ])
            ->log("Revolving fund {$record->fund_code} clarification response was submitted by {$requestor->name} ({$requestor->position})");

        $approvers = app(RevolvingFundApprovalFlowService::class)->getCurrentPendingApprovers($record);

        if ($approvers->isNotEmpty()) {
            Notification::make()
                ->title('Clarification Response Received')
                ->body("{$requestor?->name} responded to {$record->fund_code}: {$remarks}")
                ->actions([
                    NotificationAction::make('markAsRead')
                        ->button()
                        ->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.for-approval-revolving-funds.view', ['record' => $record->id])),
                ])
                ->sendToDatabase($approvers);

            $this->sendBulkMail(
                users: $approvers,
                subject: "Revolving Fund {$record->fund_code} Clarification Response",
                title: 'Clarification Response',
                message: "{$requestor?->name} responded: {$remarks}",
                actionUrl: route('filament.admin.resources.for-approval-revolving-funds.view', ['record' => $record->id]),
                actionLabel: 'Review Response',
            );
        }

        Notification::make()
            ->title('Response sent to approver(s).')
            ->success()
            ->send();
    }

    private function addDiscussion(
        $record,
        ?int $senderId,
        ?int $recipientId,
        string $type,
        string $remarks,
    ): RequestDiscussion
    {
        return RequestDiscussion::query()->create([
            'discussable_type' => RevolvingFund::class,
            'discussable_id' => $record->id,
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'type' => $type,
            'remarks' => $remarks,
        ]);
    }

    private function sendMail(
        User    $user,
        string  $subject,
        string  $title,
        string  $message,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
    ): void
    {
        if (!$user->email) {
            return;
        }

        Mail::to($user->email)->send(new RevolvingFundDiscussionMail(
            subjectLine: $subject,
            title: $title,
            messageBody: $message,
            actionUrl: $actionUrl,
            actionLabel: $actionLabel,
        ));
    }

    private function sendBulkMail(
        Collection $users,
        string     $subject,
        string     $title,
        string     $message,
        ?string    $actionUrl = null,
        ?string    $actionLabel = null,
    ): void
    {
        $users
            ->filter(fn($user) => filled($user?->email))
            ->each(function ($user) use ($subject, $title, $message, $actionUrl, $actionLabel): void {
                $this->sendMail(
                    user: $user,
                    subject: $subject,
                    title: $title,
                    message: $message,
                    actionUrl: $actionUrl,
                    actionLabel: $actionLabel,
                );
            });
    }
}
