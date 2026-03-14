<?php

namespace App\Services\RevolvingFund;

use App\Models\RevolvingFund\ForApprovalReplenishment;
use App\Models\RevolvingFund\ReplenishmentApproval;
use App\Models\RevolvingFund\ReplenishmentApprovalRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReplenishmentApprovalFlowService
{
    public function resolveRule($record): ?ReplenishmentApprovalRule
    {
        $amount = (float)$record->total_amount;

        return ReplenishmentApprovalRule::query()
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
        if ($record->replenishmentApprovals()->exists()) {
            return;
        }

        $rule = $this->resolveRule($record);

        if (!$rule) {
            throw new RuntimeException('No active replenishment approval rule found. Configure one in Replenishment Approval Rules.');
        }

        $steps = $rule->steps()
            ->orderBy('step_order')
            ->orderBy('id')
            ->get(['role_name', 'step_order'])
            ->filter(fn($step) => filled($step->role_name))
            ->values();

        if ($steps->isEmpty()) {
            throw new RuntimeException('The matched replenishment approval rule has no configured approver roles.');
        }

        $record->replenishmentApprovals()->createMany(
            $steps->map(function ($step, int $index) {
                return [
                    'step_order' => $index + 1,
                    'role_name' => $step->role_name,
                    'status' => 'pending',
                ];
            })->all()
        );
    }

    public function filterPendingForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query->whereIn('status', ['pending', 'returned']);
        }

        $roles = $user->roles()->pluck('name')->all();

        if (empty($roles)) {
            return $query->whereRaw('1 = 0');
        }

        $hasDepartmentHeadRole = in_array('department_head', $roles, true);
        $otherRoles = array_values(array_diff($roles, ['department_head']));

        return $query
            ->whereIn('status', ['pending', 'returned'])
            ->where(function (Builder $scopedQuery) use ($hasDepartmentHeadRole, $otherRoles, $user): void {
                if (!empty($otherRoles)) {
                    $scopedQuery->orWhereExists(function ($subQuery) use ($otherRoles) {
                        $subQuery->selectRaw('1')
                            ->from('replenishment_approvals as ra')
                            ->whereColumn('ra.replenishment_id', 'replenishments.id')
                            ->where('ra.status', 'pending')
                            ->whereIn('ra.role_name', $otherRoles)
                            ->whereRaw(
                                "ra.id = (
                                    SELECT ra2.id
                                    FROM replenishment_approvals as ra2
                                    WHERE ra2.replenishment_id = replenishments.id
                                      AND ra2.status = ?
                                    ORDER BY COALESCE(ra2.step_order, ra2.id), ra2.id
                                    LIMIT 1
                                )",
                                ['pending']
                            );
                    });
                }

                if ($hasDepartmentHeadRole) {
                    $scopedQuery->orWhereExists(function ($subQuery) use ($user) {
                        $subQuery->selectRaw('1')
                            ->from('replenishment_approvals as ra')
                            ->whereColumn('ra.replenishment_id', 'replenishments.id')
                            ->where('ra.status', 'pending')
                            ->where('ra.role_name', 'department_head')
                            ->whereRaw(
                                "ra.id = (
                                    SELECT ra2.id
                                    FROM replenishment_approvals as ra2
                                    WHERE ra2.replenishment_id = replenishments.id
                                      AND ra2.status = ?
                                    ORDER BY COALESCE(ra2.step_order, ra2.id), ra2.id
                                    LIMIT 1
                                )",
                                ['pending']
                            )
                            ->whereExists(function ($departmentSubQuery) use ($user) {
                                $departmentSubQuery->selectRaw('1')
                                    ->from('revolving_funds as rf')
                                    ->join('users as requestor', 'requestor.id', '=', 'rf.user_id')
                                    ->whereColumn('rf.id', 'replenishments.revolving_fund_id')
                                    ->where('requestor.department_id', $user->department_id);
                            });
                    });
                }
            });
    }

    public function userCanReview(ForApprovalReplenishment $record, User $user): bool
    {
        return $this->getPendingApprovalForUser($record, $user) !== null;
    }

    public function applyApproval(ForApprovalReplenishment $record, User $user): array
    {
        return DB::transaction(function () use ($record, $user): array {
            $this->initializeApprovals($record);
            $record->refresh();

            $approval = $this->getPendingApprovalForUser($record, $user);

            if (!$approval) {
                throw new RuntimeException('You are not allowed to approve this replenishment request.');
            }

            $approval->update([
                'approved_by' => $user->id,
                'status' => 'approved',
                'acted_at' => now(),
            ]);

            $roleTitle = $this->roleTitle($approval->role_name);
            $hasPending = $record->replenishmentApprovals()->where('status', 'pending')->exists();

            if ($hasPending) {
                $statusRemarks = "{$roleTitle} Approved";

                $record->update([
                    'status' => 'pending',
                    'status_remarks' => $statusRemarks,
                ]);

                return [
                    'is_final_step' => false,
                    'status' => 'pending',
                    'status_remarks' => $statusRemarks,
                    'approved_role_name' => $approval->role_name,
                ];
            }

            $statusRemarks = "{$roleTitle} Approved";

            $record->update([
                'status' => 'approved',
                'status_remarks' => $statusRemarks,
            ]);

            return [
                'is_final_step' => true,
                'status' => 'approved',
                'status_remarks' => $statusRemarks,
                'approved_role_name' => $approval->role_name,
            ];
        });
    }

    public function applyRejection(ForApprovalReplenishment $record, User $user, ?string $reason = null): array
    {
        return DB::transaction(function () use ($record, $user, $reason): array {
            $this->initializeApprovals($record);
            $record->refresh();

            $approval = $this->getPendingApprovalForUser($record, $user);

            if (!$approval) {
                throw new RuntimeException('You are not allowed to reject this replenishment request.');
            }

            $approval->update([
                'approved_by' => $user->id,
                'status' => 'declined',
                'acted_at' => now(),
            ]);

            $roleTitle = $this->roleTitle($approval->role_name);
            $statusRemarks = "{$roleTitle} Rejected";

            $record->update([
                'status' => 'rejected',
                'status_remarks' => $statusRemarks,
                'reason_for_rejection' => $reason,
            ]);

            return [
                'status' => 'rejected',
                'status_remarks' => $statusRemarks,
                'rejected_role_name' => $approval->role_name,
            ];
        });
    }

    public function getCurrentPendingApprovers($record): Collection
    {
        $this->initializeApprovals($record);

        $current = $record->replenishmentApprovals()
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->orderBy('id')
            ->first();

        if (!$current) {
            return collect();
        }

        $usersQuery = User::query()
            ->role($current->role_name);

        if ($current->role_name === 'department_head') {
            $departmentId = (int)($record->revolvingFund?->user?->department_id ?? 0);

            if ($departmentId === 0) {
                return collect();
            }

            $usersQuery->where('department_id', $departmentId);
        }

        return $usersQuery->get();
    }

    private function getPendingApprovalForUser(ForApprovalReplenishment $record, User $user): ?ReplenishmentApproval
    {
        $currentPending = $record->replenishmentApprovals()
            ->where('status', 'pending')
            ->orderByRaw('COALESCE(step_order, id)')
            ->orderBy('id')
            ->first();

        if (!$currentPending) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            return $currentPending;
        }

        $roles = $user->roles()->pluck('name')->all();

        if (empty($roles)) {
            return null;
        }

        if (!in_array($currentPending->role_name, $roles, true)) {
            return null;
        }

        if ($currentPending->role_name === 'department_head') {
            $requestorDepartmentId = (int)($record->revolvingFund?->user?->department_id ?? 0);
            $reviewerDepartmentId = (int)($user->department_id ?? 0);

            if ($requestorDepartmentId === 0 || $requestorDepartmentId !== $reviewerDepartmentId) {
                return null;
            }
        }

        return $currentPending;
    }

    private function roleTitle(string $roleName): string
    {
        return str($roleName)->replace('_', ' ')->title()->toString();
    }
}
