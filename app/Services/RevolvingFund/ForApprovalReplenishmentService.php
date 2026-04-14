<?php

namespace App\Services\RevolvingFund;

use App\Filament\Resources\ForApprovalReplenishmentResource;
use App\Mail\RevolvingFund\RevolvingFundDiscussionMail;
use App\Models\RevolvingFund\ForApprovalReplenishment;
use App\Models\RevolvingFund\Replenishment;
use App\Models\RevolvingFund\RequestDiscussion;
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class ForApprovalReplenishmentService
{
    public function filterVisibleForUser(Builder $query, User $user): Builder
    {
        $pendingQuery = app(ReplenishmentApprovalFlowService::class)
            ->filterPendingForUser(clone $query, $user)
            ->select('replenishments.id');

        return $query->where(function (Builder $visibleQuery) use ($pendingQuery, $user): void {
            $visibleQuery
                ->whereIn('replenishments.id', $pendingQuery)
                ->orWhereExists(function ($subQuery) use ($user) {
                    $subQuery->selectRaw('1')
                        ->from('replenishment_approvals as ra')
                        ->whereColumn('ra.replenishment_id', 'replenishments.id')
                        ->where('ra.approved_by', $user->id)
                        ->whereIn('ra.status', ['approved', 'declined']);
                });
        });
    }

    public function filterPendingForUser(Builder $query, User $user): Builder
    {
        return app(ReplenishmentApprovalFlowService::class)->filterPendingForUser($query, $user);
    }

    public function userCanReview($replenishment, User $user): bool
    {
        $flowRecord = ForApprovalReplenishment::query()->find($replenishment->id);

        if (!$flowRecord) {
            return false;
        }

        return app(ReplenishmentApprovalFlowService::class)->userCanReview($flowRecord, $user);
    }

    public function getCurrentStepConfiguration($replenishment, User $user): ?array
    {
        $flowRecord = ForApprovalReplenishment::query()->find($replenishment->id);

        if (!$flowRecord) {
            return null;
        }

        $flowService = app(ReplenishmentApprovalFlowService::class);
        $pendingApproval = $flowService->getPendingApprovalForUser($flowRecord, $user);

        if (!$pendingApproval) {
            return null;
        }

        return $flowService->getStepConfiguration($flowRecord, $pendingApproval);
    }

    public function submitItemReview(
        $replenishment,
        array $approvedItemIds,
        ?string $remarks = null,
        array $stepFormData = [],
        bool $rejectRequest = false,
    ): void
    {
        $reviewer = Auth::user();

        if (!$reviewer) {
            throw new RuntimeException('You are not authorized to review this request.');
        }

        $replenishment->loadMissing('revolvingFund.user', 'replenishmentItems');

        if (!$this->userCanReview($replenishment, $reviewer)) {
            throw new RuntimeException('You are not authorized to review this request.');
        }

        $allItems = $replenishment->replenishmentItems;

        if ($allItems->isEmpty()) {
            throw new RuntimeException('No replenishment items found for this request.');
        }

        $reviewableItems = $allItems
            ->filter(fn($item) => $item->is_approved !== false)
            ->values();

        if ($reviewableItems->isEmpty()) {
            throw new RuntimeException('All items are already marked as not approved.');
        }

        $stepConfig = $this->getCurrentStepConfiguration($replenishment, $reviewer);

        if (!$stepConfig) {
            throw new RuntimeException('Step configuration for this approver could not be resolved.');
        }

        $useItemSelection = (bool)($stepConfig['use_item_selection'] ?? true);
        $canApprove = (bool)($stepConfig['can_approve'] ?? true);
        $canVerify = (bool)($stepConfig['can_verify'] ?? false);
        $canReject = (bool)($stepConfig['can_reject'] ?? true);
        $canAdvanceWithApprovedItems = $canApprove || $canVerify;

        if ($useItemSelection) {
            $approvedIds = collect($approvedItemIds)
                ->map(fn($id) => (int)$id)
                ->intersect($reviewableItems->pluck('id'))
                ->values();
        } else {
            $approvedIds = $rejectRequest
                ? collect()
                : $reviewableItems->pluck('id')->map(fn($id) => (int)$id)->values();
        }

        if ($approvedIds->isNotEmpty() && !$canAdvanceWithApprovedItems) {
            throw new RuntimeException('This step is not allowed to approve or verify replenishment requests.');
        }

        if ($approvedIds->isEmpty() && !$canReject) {
            throw new RuntimeException('This approval step is not allowed to reject requests.');
        }

        if ($approvedIds->isEmpty() && blank($remarks)) {
            throw new RuntimeException('Remarks are required when no items are approved.');
        }

        $sanitizedStepFormData = $this->sanitizeStepFormData(
            formData: $stepFormData,
            stepSchema: (array)($stepConfig['form_schema'] ?? []),
        );

        $approvalResult = DB::transaction(function () use ($replenishment, $allItems, $reviewableItems, $approvedIds, $reviewer, $remarks, $sanitizedStepFormData): array {
            $replenishment->replenishmentItems()
                ->whereIn('id', $reviewableItems->pluck('id')->all())
                ->update([
                    'is_approved' => false,
                    'approval_remarks' => $remarks,
                    'reviewed_by' => $reviewer->id,
                    'reviewed_at' => now(),
                ]);

            if ($approvedIds->isNotEmpty()) {
                $replenishment->replenishmentItems()
                    ->whereIn('id', $approvedIds->all())
                    ->update([
                        'is_approved' => true,
                        'approval_remarks' => null,
                        'reviewed_by' => $reviewer->id,
                        'reviewed_at' => now(),
                    ]);
            }

            $approvedTotal = (float)$replenishment->replenishmentItems()
                ->where('is_approved', true)
                ->sum('amount');
            $rejectedTotal = (float)$replenishment->replenishmentItems()
                ->where('is_approved', false)
                ->sum('amount');
            $approvedCount = (int)$approvedIds->count();
            $itemCount = (int)$allItems->count();
            $remainingAmount = max((float)$replenishment->initial_amount - $approvedTotal, 0);

            $isRejected = $approvedCount === 0;
            $flowService = app(ReplenishmentApprovalFlowService::class);
            $flowRecord = ForApprovalReplenishment::query()->findOrFail($replenishment->id);

            $flowService->initializeApprovals($flowRecord);

            if ($isRejected) {
                $flowResult = $flowService->applyRejection($flowRecord, $reviewer, $remarks, $sanitizedStepFormData);
                $statusRemarks = (string)($flowResult['status_remarks'] ?? 'Rejected');
            } else {
                $flowResult = $flowService->applyApproval($flowRecord, $reviewer, $sanitizedStepFormData);
                $statusRemarks = (string)($flowResult['status_remarks'] ?? 'Approved');
            }

            $replenishment->update([
                'total_amount' => $approvedTotal,
                'remaining_amount' => $remainingAmount,
                'amount_to_return' => $rejectedTotal,
                'amount_to_deduct' => $rejectedTotal,
                'status' => $flowRecord->fresh()->status,
                'status_remarks' => $statusRemarks,
                'reason_for_rejection' => $isRejected ? $remarks : null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            $fund = $replenishment->revolvingFund;

            if ($fund && ($isRejected || ($flowResult['is_final_step'] ?? false) === true)) {
                $fund->update([
                    'remaining_amount' => $remainingAmount,
                ]);
            }

            activity()
                ->causedBy($reviewer)
                ->performedOn($fund ?? $replenishment)
                ->event('replenishment_items_reviewed')
                ->withProperties([
                    'replenishment_id' => $replenishment->id,
                    'fund_code' => $fund?->fund_code,
                    'approved_item_ids' => $approvedIds->all(),
                    'approved_count' => $approvedCount,
                    'total_items' => $itemCount,
                    'approved_total_amount' => $approvedTotal,
                    'rejected_total_amount' => $rejectedTotal,
                    'amount_to_return' => $rejectedTotal,
                    'amount_to_deduct' => $rejectedTotal,
                    'remaining_amount' => $remainingAmount,
                    'status' => $replenishment->status,
                    'status_remarks' => $statusRemarks,
                    'remarks' => $remarks,
                    'step_form_data' => $sanitizedStepFormData,
                ])
                ->log("Replenishment request for {$fund?->fund_code} was reviewed by {$reviewer->name} ({$reviewer->position})");

            return [
                'is_rejected' => $isRejected,
                'is_final_step' => (bool)($flowResult['is_final_step'] ?? $isRejected),
                'status_remarks' => $statusRemarks,
            ];
        });

        $fresh = $replenishment->fresh(['revolvingFund.user']);

        if (!$fresh?->revolvingFund?->user) {
            return;
        }

        $isRejected = (bool)($approvalResult['is_rejected'] ?? false);
        $isFinalStep = (bool)($approvalResult['is_final_step'] ?? false);

        $notificationTitle = $isRejected
            ? 'Replenishment Rejected'
            : ($isFinalStep ? 'Replenishment Approved' : 'Replenishment Approval Step Completed');

        $notificationBody = $isRejected
            ? "Your replenishment request for {$fresh->revolvingFund->fund_code} was rejected."
            : ($isFinalStep
                ? "Your replenishment request for {$fresh->revolvingFund->fund_code} was approved."
                : "Your replenishment request for {$fresh->revolvingFund->fund_code} advanced to the next approval step.");

        Notification::make()
            ->title($notificationTitle)
            ->body($notificationBody)
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),
                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.replenishments.view', ['record' => $fresh->id])),
            ])
            ->sendToDatabase($fresh->revolvingFund->user);

        $this->sendMail(
            user: $fresh->revolvingFund->user,
            subject: "{$notificationTitle} - {$fresh->revolvingFund->fund_code}",
            title: $notificationTitle,
            message: $notificationBody,
            actionUrl: route('filament.admin.resources.replenishments.view', ['record' => $fresh->id]),
            actionLabel: 'View Replenishment',
        );

        if (!$isRejected && !$isFinalStep) {
            $this->notifyCurrentApprovers($fresh);
        }
    }

    public function applyReplenishmentAmount($replenishment, float $amount): void
    {
        $user = Auth::user();

        if (!$user) {
            throw new RuntimeException('You are not authorized to replenish this request.');
        }

        if ($amount <= 0) {
            throw new RuntimeException('Amount must be greater than zero.');
        }

        DB::transaction(function () use ($replenishment, $amount, $user): void {
            $replenishment->loadMissing('revolvingFund');

            if ((string)$replenishment->status === 'replenished') {
                throw new RuntimeException('This request is already replenished.');
            }

            $recordRemaining = (float)$replenishment->remaining_amount;
            $initialAmount = (float)$replenishment->initial_amount;
            $gapToRestore = max($initialAmount - $recordRemaining, 0);
            $amountAppliedToFund = min($amount, $gapToRestore);
            $amountToReimburse = max($amount - $amountAppliedToFund, 0);
            $newRemaining = min($recordRemaining + $amountAppliedToFund, $initialAmount);

            $replenishment->update([
                'old_remaining_amount' => $recordRemaining,
                'remaining_amount' => $newRemaining,
                'amount_to_reimburse' => $amountToReimburse,
                'status' => 'replenished',
                'status_remarks' => 'Replenished',
                'replenished_by' => $user->id,
                'replenished_at' => now(),
            ]);

            $fundCurrentRemaining = null;
            $fundNewRemaining = null;

            if ($replenishment->revolvingFund) {
                $fundCurrentRemaining = (float)$replenishment->revolvingFund->remaining_amount;
                $fundNewRemaining = min($fundCurrentRemaining + $amountAppliedToFund, $initialAmount);

                $replenishment->revolvingFund->update([
                    'remaining_amount' => $fundNewRemaining,
                ]);
            }

            activity()
                ->causedBy($user)
                ->performedOn($replenishment->revolvingFund ?? $replenishment)
                ->event('replenished')
                ->withProperties([
                    'replenishment_id' => $replenishment->id,
                    'fund_code' => $replenishment->revolvingFund?->fund_code,
                    'added_amount' => $amount,
                    'amount_applied_to_fund' => $amountAppliedToFund,
                    'amount_to_reimburse' => $amountToReimburse,
                    'record_remaining_amount' => $recordRemaining,
                    'previous_fund_remaining_amount' => $fundCurrentRemaining ?? null,
                    'new_fund_remaining_amount' => $fundNewRemaining ?? null,
                    'replenished_by' => $user->id,
                    'replenished_at' => now()->toDateTimeString(),
                ])
                ->log("Replenishment request for {$replenishment->revolvingFund?->fund_code} was replenished by {$user->name} ({$user->position}).");
        });
    }

    public function notifyCurrentApprovers($replenishment): void
    {
        $replenishment->loadMissing('revolvingFund.user');

        $approvers = app(ReplenishmentApprovalFlowService::class)
            ->getCurrentPendingApprovers(ForApprovalReplenishment::query()->findOrFail($replenishment->id));

        if ($approvers->isEmpty()) {
            return;
        }

        $requestor = $replenishment->revolvingFund?->user;
        $fundCode = $replenishment->revolvingFund?->fund_code ?? "Replenishment #{$replenishment->id}";

        Notification::make()
            ->title('Replenishment Request For Approval')
            ->body(($requestor?->name ?? 'A user') . " submitted replenishment request for {$fundCode}.")
            ->actions([
                NotificationAction::make('markAsRead')
                    ->button()
                    ->markAsRead(),
                NotificationAction::make('view')
                    ->link()
                    ->url(route('filament.admin.resources.for-approval-replenishments.view', ['record' => $replenishment->id])),
            ])
            ->sendToDatabase($approvers);

        $approvers
            ->filter(fn($user) => filled($user?->email))
            ->each(function (User $user) use ($requestor, $fundCode, $replenishment): void {
                $this->sendMail(
                    user: $user,
                    subject: "Replenishment Request {$fundCode}",
                    title: 'Replenishment Request For Approval',
                    message: ($requestor?->name ?? 'A user') . " submitted replenishment request for {$fundCode}.",
                    actionUrl: route('filament.admin.resources.for-approval-replenishments.view', ['record' => $replenishment->id]),
                    actionLabel: 'Review Request',
                );
            });
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
            'status' => 'returned',
            'status_remarks' => 'Returned for Clarification',
        ]);

        $discussion = $this->addDiscussion(
            record: $record,
            senderId: $approver?->id,
            recipientId: $record->revolvingFund?->user_id,
            type: 'return',
            remarks: $remarks,
        );

        activity()
            ->causedBy($approver)
            ->performedOn($record)
            ->event('returned_for_clarification')
            ->withProperties([
                'replenishment_id' => $record->id,
                'fund_code' => $record->revolvingFund?->fund_code,
                'remarks' => $remarks,
                'discussion_id' => $discussion->id,
            ])
            ->log("Replenishment request for {$record->revolvingFund?->fund_code} was returned for clarification by {$approver?->name} ({$approver?->position})");

        if ($record->revolvingFund?->user) {
            Notification::make()
                ->title('Replenishment Clarification Requested')
                ->body("Your replenishment request for {$record->revolvingFund?->fund_code} was returned with remarks: {$remarks}")
                ->actions([
                    NotificationAction::make('markAsRead')
                        ->button()
                        ->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.replenishments.view', ['record' => $record->id])),
                ])
                ->sendToDatabase($record->revolvingFund->user);
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
                'replenishment_id' => $record->id,
                'fund_code' => $record->revolvingFund?->fund_code,
                'remarks' => $remarks,
                'discussion_id' => $discussion->id,
            ])
            ->log("Replenishment clarification response for {$record->revolvingFund?->fund_code} was submitted by {$requestor?->name} ({$requestor?->position})");

        $approvers = app(ReplenishmentApprovalFlowService::class)
            ->getCurrentPendingApprovers(ForApprovalReplenishment::query()->findOrFail($record->id));

        if ($approvers->isNotEmpty()) {
            Notification::make()
                ->title('Clarification Response Received')
                ->body("{$requestor?->name} responded to the replenishment for {$record->revolvingFund?->fund_code}: {$remarks}")
                ->actions([
                    NotificationAction::make('markAsRead')
                        ->button()
                        ->markAsRead(),
                    NotificationAction::make('view')
                        ->link()
                        ->url(route('filament.admin.resources.for-approval-replenishments.view', ['record' => $record->id])),
                ])
                ->sendToDatabase($approvers);
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
            'discussable_type' => Replenishment::class,
            'discussable_id' => $record->id,
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'type' => $type,
            'remarks' => $remarks,
        ]);
    }

    private function sanitizeStepFormData(array $formData, array $stepSchema): array
    {
        if (empty($stepSchema)) {
            return [];
        }

        $result = [];

        foreach ($stepSchema as $fieldConfig) {
            $key = (string)($fieldConfig['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $label = (string)($fieldConfig['label'] ?? $key);
            $required = (bool)($fieldConfig['required'] ?? false);
            $type = (string)($fieldConfig['type'] ?? 'text');
            $value = $formData[$key] ?? null;

            if ($required && blank($value)) {
                throw new RuntimeException("{$label} is required.");
            }

            if (blank($value)) {
                $result[$key] = null;
                continue;
            }

            $result[$key] = match ($type) {
                'number' => (float)$value,
                'date' => (string)$value,
                default => (string)$value,
            };
        }

        return $result;
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

    /**
     * @param $record
     * @param array $data
     * @return void
     */
    public function submitItemReviewAction($record, array $data): void
    {
        try {
            $this->submitItemReview(
                $record,
                $data['approved_item_ids'] ?? [],
                $data['remarks'] ?? null,
                $data['step_form_data'] ?? [],
                (bool)($data['reject_request'] ?? false),
            );

            Notification::make()
                ->title('Replenishment review submitted.')
                ->success()
                ->send();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * @param $record
     * @param $amount_to_add
     * @return void
     */
    public function replenishAction($record, $amount_to_add): void
    {
        try {
            $this->applyReplenishmentAmount(
                $record,
                (float)($amount_to_add ?? 0),
            );

            Notification::make()
                ->title('Replenishment amount applied.')
                ->success()
                ->send();

            $this->redirect(ForApprovalReplenishmentResource::getUrl('view', ['record' => $record]));
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
