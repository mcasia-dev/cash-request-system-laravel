<?php
namespace App\Services;

use App\Enums\CashRequest\Status;
use App\Models\ApprovalRule;
use App\Models\CashRequestApproval;
use App\Models\User;
use App\Services\Remarks\StatusRemarkResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CashRequestApprovalFlowService
{
    public function __construct(
        private readonly StatusRemarkResolver $remarkResolver
    ) {
    }

    /**
     * Resolve the applicable approval rule for the request based on nature and amount.
     */
    public function resolveRule($record): ?ApprovalRule
    {
        $amount = (float) $record->requesting_amount;

        return ApprovalRule::query()
            ->where('is_active', true)
            ->where('nature', $record->nature_of_request)
            ->where(function (Builder $query) use ($amount) {
                $query->whereNull('min_amount')
                    ->orWhere('min_amount', '<=', $amount);
            })
            ->where(function (Builder $query) use ($amount) {
                $query->whereNull('max_amount')
                    ->orWhere('max_amount', '>=', $amount);
            })
            ->whereHas('approvalRuleSteps')
            ->orderByRaw('(COALESCE(max_amount, 999999999) - COALESCE(min_amount, 0)) ASC')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Initialize approval records for the request based on the matched rule.
     */
    public function initializeApprovals($record): void
    {
        if ($record->cashRequestApprovals()->exists()) {
            return;
        }

        $rule = $this->resolveRule($record);

        if (! $rule) {
            throw new RuntimeException('No active approval rule found for this request.');
        }

        $steps = $rule->approvalRuleSteps()
            ->orderBy('step_order')
            ->orderBy('id')
            ->get(['role_name', 'step_order'])
            ->filter(fn($step) => filled($step->role_name))
            ->values();

        if ($steps->isEmpty()) {
            throw new RuntimeException('The matched approval rule has no configured approver roles.');
        }

        $record->cashRequestApprovals()->createMany(
            $steps->map(function ($step, int $index) {
                return [
                    'step_order' => $index + 1,
                    'role_name' => $step->role_name,
                    'status' => Status::PENDING->value,
                ];
            })->all()
        );
    }

    /**
     * Resolve the current pending approval row id for this request.
     */
    private function getCurrentPendingApprovalId($record): ?int
    {
        $currentApprovalId = $record->cashRequestApprovals()
            ->where('status', Status::PENDING->value)
            ->orderByRaw('COALESCE(step_order, id)')
            ->orderBy('id')
            ->value('id');

        if ($currentApprovalId === null) {
            return null;
        }

        return (int) $currentApprovalId;
    }

    /**
     * Scope query to requests where the current pending step is one of the given roles.
     *
     * @param Builder $query
     * @param array<int, string> $roles
     */
    private function whereCurrentStepRoleIn(Builder $query, array $roles): Builder
    {
        return $query
            ->whereExists(function ($subquery) use ($roles) {
                $subquery->selectRaw('1')
                    ->from('cash_request_approvals as cra')
                    ->whereColumn('cra.cash_request_id', 'cash_requests.id')
                    ->where('cra.status', Status::PENDING->value)
                    ->whereIn('cra.role_name', $roles)
                    ->whereRaw(
                        'cra.id = (
                            SELECT cra2.id
                            FROM cash_request_approvals as cra2
                            WHERE cra2.cash_request_id = cash_requests.id
                              AND cra2.status = ?
                            ORDER BY COALESCE(cra2.step_order, cra2.id), cra2.id
                            LIMIT 1
                        )',
                        [Status::PENDING->value]
                    );
            });
    }

    /**
     * Constrain the pending approvals list to those the given user can act on.
     */
    public function filterPendingForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query->where('status', Status::PENDING->value);
        }

        $roles = $user->roles()->pluck('name')->all();

        if (empty($roles)) {
            return $query->whereRaw('1 = 0');
        }

        return $this->whereCurrentStepRoleIn(
            $query->where('status', Status::PENDING->value),
            $roles
        );
    }

    /**
     * Determine whether the user can review the given request.
     */
    public function userCanReview($record, User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->getPendingApprovalForUser($record, $user) !== null;
    }

    /**
     * Apply an approval for the user and update request status and remarks.
     */
    public function applyApproval($record, User $user): array
    {
        return DB::transaction(function () use ($record, $user): array {
            $this->initializeApprovals($record);

            $record->refresh();
            $approval = $this->getPendingApprovalForUser($record, $user);

            if (! $approval) {
                throw new RuntimeException('You are not allowed to approve this request.');
            }

            $approval->update([
                'approved_by' => (string) $user->id,
                'status'      => 'approved',
                'acted_at'    => now(),
            ]);

            $remark     = $this->approvedRemarkByRole($approval->role_name);
            $hasPending = $record->cashRequestApprovals()->where('status', 'pending')->exists();

            if ($hasPending) {
                $record->update([
                    'status'         => Status::PENDING->value,
                    'status_remarks' => $remark,
                ]);

                return [
                    'status_remarks'           => $remark,
                    'approved_remarks_by_role' => $remark,
                    'is_final_step'            => false,
                ];
            }

            $record->update([
                'status'         => Status::IN_PROGRESS->value,
                'status_remarks' => $this->resolveFinalApprovalRemark($record),
            ]);

            $record->refresh();

            return [
                'status_remarks'           => $record->status_remarks,
                'approved_remarks_by_role' => $remark,
                'is_final_step'            => true,
            ];
        });
    }

    /**
     * Apply a rejection for the user and mark the request as rejected.
     */
    public function applyRejection($record, User $user, string $reason): string
    {
        return DB::transaction(function () use ($record, $user, $reason): string {
            $this->initializeApprovals($record);

            $record->refresh();
            $approval = $this->getPendingApprovalForUser($record, $user);

            if (! $approval) {
                throw new RuntimeException('You are not allowed to reject this request.');
            }

            $approval->update([
                'approved_by' => (string) $user->id,
                'status'      => 'declined',
                'acted_at'    => now(),
            ]);

            $remark = $this->rejectedRemarkByRole($approval->role_name);

            $record->update([
                'status'               => Status::REJECTED->value,
                'status_remarks'       => $remark,
                'reason_for_rejection' => $reason,
            ]);

            return $remark;
        });
    }

    /**
     * Get the pending approval row for the user based on their roles.
     */
    private function getPendingApprovalForUser($record, User $user): ?CashRequestApproval
    {
        $currentApprovalId = $this->getCurrentPendingApprovalId($record);

        if ($currentApprovalId === null) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            return $record->cashRequestApprovals()
                ->where('status', Status::PENDING->value)
                ->where('id', $currentApprovalId)
                ->orderBy('id')
                ->first();
        }

        $roles = $user->roles()->pluck('name')->all();

        if (empty($roles)) {
            return null;
        }

        return $record->cashRequestApprovals()
            ->where('status', Status::PENDING->value)
            ->where('id', $currentApprovalId)
            ->whereIn('role_name', $roles)
            ->orderBy('id')
            ->first();
    }

    /**
     * Resolve the approved status remark string for the given role.
     */
    private function approvedRemarkByRole(string $role): string
    {
        return $this->remarkResolver->approveByRole($role);
    }

    /**
     * Resolve the rejected status remark string for the given role.
     */
    private function rejectedRemarkByRole(string $role): string
    {
        return $this->remarkResolver->rejectByRole($role);
    }

    /**
     * Resolve the next status remark after final approval based on request type.
     */
    private function resolveFinalApprovalRemark($record): string
    {
        return $this->remarkResolver->finalRemarkForNature($record->nature_of_request);
    }
}
