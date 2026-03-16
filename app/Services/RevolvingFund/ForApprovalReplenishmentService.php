<?php

namespace App\Services\RevolvingFund;

use App\Enums\RevolvingFund\Status;
use App\Mail\RevolvingFund\RevolvingFundDiscussionMail;
use App\Models\RevolvingFund\ForApprovalReplenishment;
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

        if (! $flowRecord) {
            return null;
        }

        $flowService = app(ReplenishmentApprovalFlowService::class);
        $pendingApproval = $flowService->getPendingApprovalForUser($flowRecord, $user);

        if (! $pendingApproval) {
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

        if (! $stepConfig) {
            throw new RuntimeException('Step configuration for this approver could not be resolved.');
        }

        $useItemSelection = (bool) ($stepConfig['use_item_selection'] ?? true);
        $canApprove = (bool) ($stepConfig['can_approve'] ?? true);
        $canReject = (bool) ($stepConfig['can_reject'] ?? true);

        if ($useItemSelection) {
            $approvedIds = collect($approvedItemIds)
                ->map(fn($id) => (int)$id)
                ->intersect($reviewableItems->pluck('id'))
                ->values();
        } else {
            $approvedIds = $rejectRequest
                ? collect()
                : $reviewableItems->pluck('id')->map(fn($id) => (int) $id)->values();
        }

        if ($approvedIds->isNotEmpty() && ! $canApprove) {
            throw new RuntimeException('This approval step is not allowed to approve requests.');
        }

        if ($approvedIds->isEmpty() && ! $canReject) {
            throw new RuntimeException('This approval step is not allowed to reject requests.');
        }

        if ($approvedIds->isEmpty() && blank($remarks)) {
            throw new RuntimeException('Remarks are required when no items are approved.');
        }

        $sanitizedStepFormData = $this->sanitizeStepFormData(
            formData: $stepFormData,
            stepSchema: (array) ($stepConfig['form_schema'] ?? []),
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

            if ($fund) {
                $fundUpdate = ['status_remarks' => $statusRemarks];

                if ($isRejected || ($flowResult['is_final_step'] ?? false) === true) {
                    $fundUpdate['remaining_amount'] = $remainingAmount;
                    $fundUpdate['status'] = $isRejected ? Status::APPROVED->value : Status::REPLENISHED->value;
                }

                $fund->update($fundUpdate);
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
            $newRemaining = $recordRemaining + $amount;

            if ($newRemaining > $initialAmount) {
                throw new RuntimeException('Amount to add is invalid. Remaining amount cannot exceed the initial amount.');
            }

            $replenishment->update([
                'status' => 'replenished',
                'status_remarks' => 'Replenished',
                'replenished_by' => $user->id,
                'replenished_at' => now(),
            ]);

            if ($replenishment->revolvingFund) {
                $fundCurrentRemaining = (float)$replenishment->revolvingFund->remaining_amount;
                $fundNewRemaining = $fundCurrentRemaining + $amount;

                $replenishment->revolvingFund->update([
                    'remaining_amount' => $fundNewRemaining,
                    'status' => Status::REPLENISHED->value,
                    'status_remarks' => 'Replenished',
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

    private function sanitizeStepFormData(array $formData, array $stepSchema): array
    {
        if (empty($stepSchema)) {
            return [];
        }

        $result = [];

        foreach ($stepSchema as $fieldConfig) {
            $key = (string) ($fieldConfig['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $label = (string) ($fieldConfig['label'] ?? $key);
            $required = (bool) ($fieldConfig['required'] ?? false);
            $type = (string) ($fieldConfig['type'] ?? 'text');
            $value = $formData[$key] ?? null;

            if ($required && blank($value)) {
                throw new RuntimeException("{$label} is required.");
            }

            if (blank($value)) {
                $result[$key] = null;
                continue;
            }

            $result[$key] = match ($type) {
                'number' => (float) $value,
                'date' => (string) $value,
                default => (string) $value,
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
}
