<?php

namespace App\Services\RevolvingFund;

use App\Enums\RevolvingFund\Status;
use App\Mail\RevolvingFund\RevolvingFundDiscussionMail;
use App\Models\RevolvingFund\ForApprovalReplenishment;
use App\Models\RevolvingFund\RequestDiscussion;
use App\Models\RevolvingFund\RevolvingFund;
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class ReplenishmentApprovalService
{
    public function notifyDepartmentHeadsOnSubmission($replenishment): void
    {
        app(ForApprovalReplenishmentService::class)->notifyCurrentApprovers($replenishment);
    }

    public function canCurrentDepartmentHeadReview(RevolvingFund $fund): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        $replenishment = $this->getLatestActionableReplenishment($fund);

        if (!$replenishment) {
            return false;
        }

        $flowRecord = ForApprovalReplenishment::query()->find($replenishment->id);

        if (!$flowRecord) {
            return false;
        }

        return app(ReplenishmentApprovalFlowService::class)->userCanReview($flowRecord, $user);
    }

    public function approveLatestPending(RevolvingFund $fund): void
    {
        $this->ensureAuthorized($fund);
        $reviewer = Auth::user();
        $replenishment = $this->getLatestActionableReplenishment($fund);

        if (!$replenishment) {
            throw new RuntimeException('No pending replenishment request found.');
        }

        $replenishment->update([
            'status' => 'approved',
            'status_remarks' => 'Approved by Department Head',
            'reason_for_rejection' => null,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
        ]);

        $fund->update([
            'status' => Status::REPLENISHED->value,
            'status_remarks' => 'Replenishment Approved by Department Head',
        ]);

        activity()
            ->causedBy($reviewer)
            ->performedOn($fund)
            ->event('replenishment_approved')
            ->withProperties([
                'fund_code' => $fund->fund_code,
                'replenishment_id' => $replenishment->id,
                'total_amount' => $replenishment->total_amount,
                'status_remarks' => $replenishment->status_remarks,
            ])
            ->log("Replenishment request for {$fund->fund_code} was approved by {$reviewer->name} ({$reviewer->position})");

        $this->notifyRequestor($fund, 'Replenishment Approved', "Your replenishment request for {$fund->fund_code} has been approved.");
    }

    public function rejectLatestPending(RevolvingFund $fund, string $reason): void
    {
        $this->ensureAuthorized($fund);
        $reviewer = Auth::user();
        $replenishment = $this->getLatestActionableReplenishment($fund);

        if (!$replenishment) {
            throw new RuntimeException('No pending replenishment request found.');
        }

        $replenishment->update([
            'status' => 'rejected',
            'status_remarks' => 'Rejected by Department Head',
            'reason_for_rejection' => $reason,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
        ]);

        $fund->update([
            'status_remarks' => 'Replenishment Rejected by Department Head',
        ]);

        activity()
            ->causedBy($reviewer)
            ->performedOn($fund)
            ->event('replenishment_rejected')
            ->withProperties([
                'fund_code' => $fund->fund_code,
                'replenishment_id' => $replenishment->id,
                'reason_for_rejection' => $reason,
            ])
            ->log("Replenishment request for {$fund->fund_code} was rejected by {$reviewer->name} ({$reviewer->position})");

        $this->notifyRequestor($fund, 'Replenishment Rejected', "Your replenishment request for {$fund->fund_code} has been rejected. Reason: {$reason}");
    }

    public function returnLatestPending(RevolvingFund $fund, string $remarks): void
    {
        $this->ensureAuthorized($fund);
        $reviewer = Auth::user();
        $replenishment = $this->getLatestActionableReplenishment($fund);

        if (!$replenishment) {
            throw new RuntimeException('No pending replenishment request found.');
        }

        $replenishment->update([
            'status' => 'returned',
            'status_remarks' => 'Returned for Clarification',
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
        ]);

        activity()
            ->causedBy($reviewer)
            ->performedOn($fund)
            ->event('replenishment_returned')
            ->withProperties([
                'fund_code' => $fund->fund_code,
                'replenishment_id' => $replenishment->id,
                'remarks' => $remarks,
            ])
            ->log("Replenishment request for {$fund->fund_code} was returned by {$reviewer->name} ({$reviewer->position})");

        RequestDiscussion::query()->create([
            'discussable_type' => RevolvingFund::class,
            'discussable_id' => $fund->id,
            'sender_id' => $reviewer?->id,
            'recipient_id' => $fund->added_by,
            'type' => 'return',
            'remarks' => $remarks,
        ]);

        $this->notifyRequestor(
            $fund,
            'Replenishment Returned for Clarification',
            "Your replenishment request for {$fund->fund_code} was returned with remarks: {$remarks}"
        );
    }

    public function hasActionableReplenishment(RevolvingFund $fund): bool
    {
        return $this->getLatestActionableReplenishment($fund) !== null;
    }

    private function getLatestActionableReplenishment(RevolvingFund $fund)
    {
        return $fund->replenishments()
            ->whereIn('status', ['pending', 'returned'])
            ->latest('id')
            ->first();
    }

    private function ensureAuthorized(RevolvingFund $fund): void
    {
        if (!$this->canCurrentDepartmentHeadReview($fund)) {
            throw new RuntimeException('You are not authorized to review this replenishment request.');
        }
    }

    private function notifyRequestor(RevolvingFund $fund, string $title, string $body): void
    {
        $requestor = $fund->user;

        if (!$requestor) {
            return;
        }

        Notification::make()
            ->title($title)
            ->body($body)
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),
                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.revolving-funds.view', ['record' => $fund->id])),
            ])
            ->sendToDatabase($requestor);

        $this->sendMail(
            user: $requestor,
            subject: "{$title} - {$fund->fund_code}",
            title: $title,
            message: $body,
            actionUrl: route('filament.admin.resources.revolving-funds.view', ['record' => $fund->id]),
            actionLabel: 'View Revolving Fund',
        );
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
}
