<?php

namespace App\Services\RevolvingFund;

use App\Enums\RevolvingFund\Status;
use App\Enums\RevolvingFund\StatusRemarks;
use App\Models\RevolvingFund\ForApprovalRevolvingFund;
use App\Models\RevolvingFund\RevolvingFundApproval;
use App\Models\RevolvingFund\RevolvingFundApprovalRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RevolvingFundApprovalFlowService
{
    public function resolveRule($record): ?RevolvingFundApprovalRule
    {
        $amount = (float)$record->initial_amount;

        return RevolvingFundApprovalRule::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($amount) {
                $query->whereNull('min_amount')
                    ->orWhere('min_amount', '<=', $amount);
            })
            ->where(function (Builder $query) use ($amount) {
                $query->whereNull('max_amount')
                    ->orWhere('max_amount', '>=', $amount);
            })
            ->whereHas('steps')
            ->orderByRaw('(COALESCE(max_amount, 999999999) - COALESCE(min_amount, 0)) ASC')
            ->orderByDesc('id')
            ->first();
    }

    public function initializeApprovals($record): void
    {
        if ($record->revolvingFundApprovals()->exists()) {
            return;
        }

        $rule = $this->resolveRule($record);

        if (!$rule) {
            throw new RuntimeException('No active revolving fund approval rule found. Configure one in Revolving Fund Approval Rules.');
        }

        $steps = $rule->steps()
            ->orderBy('step_order')
            ->orderBy('id')
            ->get(['role_name', 'step_order', 'assigned_user_ids', 'can_approve', 'can_reject', 'form_schema'])
            ->filter(fn($step) => filled($step->role_name))
            ->values();

        if ($steps->isEmpty()) {
            throw new RuntimeException('The matched revolving fund approval rule has no configured approver roles.');
        }

        $requestor = $record->addedBy ?? $record->user ?? null;
        $needsDepartmentHeadGate = $requestor
            && ! $requestor->isSuperAdmin()
            && ! $requestor->hasRole('department_head');
        $hasDepartmentHeadStep = $steps->contains(fn($step) => $step->role_name === 'department_head');

        if ($needsDepartmentHeadGate && ! $hasDepartmentHeadStep) {
            $departmentId = $requestor->department_id;

            $departmentHeads = $departmentId
                ? User::query()
                    ->role('department_head')
                    ->where('department_id', $departmentId)
                    ->pluck('id')
                    ->map(fn($id) => (int) $id)
                    ->filter()
                    ->values()
                    ->all()
                : [];

            $steps->prepend((object) [
                'role_name' => 'department_head',
                'step_order' => 0,
                'assigned_user_ids' => $departmentHeads ?: null,
                'can_approve' => true,
                'can_reject' => true,
                'form_schema' => [],
            ]);
        }

        $record->revolvingFundApprovals()->createMany(
            $steps->map(function ($step, int $index) {
                return [
                    'step_order' => $index + 1,
                    'role_name' => $step->role_name,
                    'status' => Status::PENDING->value,
                    'assigned_user_ids' => ! empty($step->assigned_user_ids) ? array_values($step->assigned_user_ids) : null,
                    'can_approve' => (bool) ($step->can_approve ?? true),
                    'can_reject' => (bool) ($step->can_reject ?? true),
                    'step_form_data' => null,
                ];
            })->all()
        );
    }

    public function filterPendingForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query->pendingApproval();
        }

        $roles = $user->roles()->pluck('name')->all();

        if (empty($roles)) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->pendingApproval()
            ->whereExists(function ($subQuery) use ($roles, $user) {
                $subQuery->selectRaw('1')
                    ->from('revolving_fund_approvals as rfa')
                    ->whereColumn('rfa.revolving_fund_id', 'revolving_funds.id')
                    ->where('rfa.status', Status::PENDING->value)
                    ->whereIn('rfa.role_name', $roles)
                    ->where(function ($assignedUserScope) use ($user) {
                        $assignedUserScope->whereNull('rfa.assigned_user_ids')
                            ->orWhereRaw('JSON_LENGTH(rfa.assigned_user_ids) = 0')
                            ->orWhereRaw('JSON_CONTAINS(rfa.assigned_user_ids, JSON_ARRAY(?))', [$user->id]);
                    })
                    ->whereRaw(
                        "rfa.id = (
                            SELECT rfa2.id
                            FROM revolving_fund_approvals as rfa2
                            WHERE rfa2.revolving_fund_id = revolving_funds.id
                              AND rfa2.status = ?
                            ORDER BY COALESCE(rfa2.step_order, rfa2.id), rfa2.id
                            LIMIT 1
                        )",
                        [Status::PENDING->value]
                    );
            });
    }

    public function userCanReview(ForApprovalRevolvingFund $record, User $user): bool
    {
        return $this->getPendingApprovalForUser($record, $user) !== null;
    }

    public function applyApproval(ForApprovalRevolvingFund $record, User $user, array $stepFormData = []): array
    {
        return DB::transaction(function () use ($record, $user, $stepFormData): array {
            $this->initializeApprovals($record);
            $record->refresh();

            $approval = $this->getPendingApprovalForUser($record, $user);

            if (!$approval) {
                throw new RuntimeException('You are not allowed to approve this revolving fund request.');
            }

            if (! (bool) $approval->can_approve) {
                throw new RuntimeException('This step is not allowed to approve requests.');
            }

            $approval->update([
                'approved_by' => $user->id,
                'status' => 'approved',
                'acted_at' => now(),
                'step_form_data' => $stepFormData ?: null,
            ]);

            $roleTitle = $this->roleTitle($approval->role_name);
            $hasPending = $record->revolvingFundApprovals()->where('status', 'pending')->exists();

            if ($hasPending) {
                $record->update([
                    'status' => Status::IN_PROGRESS->value,
                    'status_remarks' => "{$roleTitle} Approved",
                ]);

                return [
                    'is_final_step' => false,
                    'status' => Status::IN_PROGRESS->value,
                    'status_remarks' => "{$roleTitle} Approved",
                    'approved_role_name' => $approval->role_name,
                ];
            }

            $record->update([
                'status' => Status::IN_PROGRESS->value,
                'status_remarks' => StatusRemarks::FOR_PAYMENT_PROCESSING->value,
            ]);

            return [
                'is_final_step' => true,
                'status' => Status::IN_PROGRESS->value,
                'status_remarks' => StatusRemarks::FOR_PAYMENT_PROCESSING->value,
                'approved_role_name' => $approval->role_name,
            ];
        });
    }

    public function applyRejection(ForApprovalRevolvingFund $record, User $user, array $stepFormData = []): array
    {
        return DB::transaction(function () use ($record, $user, $stepFormData): array {
            $this->initializeApprovals($record);
            $record->refresh();

            $approval = $this->getPendingApprovalForUser($record, $user);

            if (!$approval) {
                throw new RuntimeException('You are not allowed to reject this revolving fund request.');
            }

            if (! (bool) $approval->can_reject) {
                throw new RuntimeException('This step is not allowed to reject requests.');
            }

            $approval->update([
                'approved_by' => $user->id,
                'status' => 'declined',
                'acted_at' => now(),
                'step_form_data' => $stepFormData ?: null,
            ]);

            $roleTitle = $this->roleTitle($approval->role_name);
            $statusRemarks = "{$roleTitle} Rejected";

            $record->update([
                'status' => Status::REJECTED->value,
                'status_remarks' => $statusRemarks,
            ]);

            return [
                'status' => Status::REJECTED->value,
                'status_remarks' => $statusRemarks,
                'rejected_role_name' => $approval->role_name,
            ];
        });
    }

    public function getCurrentPendingApprovers($record): Collection
    {
        $this->initializeApprovals($record);

        $current = $record->revolvingFundApprovals()
            ->where('status', Status::PENDING->value)
            ->orderBy('step_order')
            ->orderBy('id')
            ->first();

        if (!$current) {
            return collect();
        }

        $query = User::query()
            ->role($current->role_name);

        $assignedUserIds = collect($current->assigned_user_ids ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->values()
            ->all();

        if (! empty($assignedUserIds)) {
            $query->whereIn('id', $assignedUserIds);
        }

        return $query->get();
    }

    private function getPendingApprovalForUser(ForApprovalRevolvingFund $record, User $user): ?RevolvingFundApproval
    {
        $currentPendingId = $record->revolvingFundApprovals()
            ->where('status', Status::PENDING->value)
            ->orderByRaw('COALESCE(step_order, id)')
            ->orderBy('id')
            ->value('id');

        if ($currentPendingId === null) {
            return null;
        }

        $currentPending = $record->revolvingFundApprovals()
            ->where('status', Status::PENDING->value)
            ->where('id', $currentPendingId)
            ->first();

        if (! $currentPending) {
            return null;
        }

        $assignedUserIds = collect($currentPending->assigned_user_ids ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->values()
            ->all();

        if ($user->isSuperAdmin()) {
            if (empty($assignedUserIds) || in_array((int) $user->id, $assignedUserIds, true)) {
                return $currentPending;
            }

            return null;
        }

        $roles = $user->roles()->pluck('name')->all();

        if (empty($roles)) {
            return null;
        }

        if (! in_array($currentPending->role_name, $roles, true)) {
            return null;
        }

        if (! empty($assignedUserIds) && ! in_array((int) $user->id, $assignedUserIds, true)) {
            return null;
        }

        return $currentPending;
    }

    public function getCurrentStepConfiguration(ForApprovalRevolvingFund $record, User $user): ?array
    {
        $approval = $this->getPendingApprovalForUser($record, $user);

        if (! $approval) {
            return null;
        }

        $rule = $this->resolveRule($record);
        $step = $rule?->steps()
            ->where('step_order', $approval->step_order)
            ->orderBy('id')
            ->first();

        return [
            'can_approve' => (bool) ($approval->can_approve ?? true),
            'can_reject' => (bool) ($approval->can_reject ?? true),
            'form_schema' => is_array($step?->form_schema) ? $step->form_schema : [],
        ];
    }

    private function roleTitle(string $roleName): string
    {
        return str($roleName)->replace('_', ' ')->title()->toString();
    }
}
