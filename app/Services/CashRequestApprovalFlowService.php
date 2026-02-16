<?php
namespace App\Services;

use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Enums\NatureOfRequestEnum;
use App\Models\ApprovalRule;
use App\Models\CashRequestApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CashRequestApprovalFlowService
{
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

    public function initializeApprovals($record): void
    {
        if ($record->cashRequestApprovals()->exists()) {
            return;
        }

        $rule = $this->resolveRule($record);

        if (! $rule) {
            throw new RuntimeException('No active approval rule found for this request.');
        }

        $roles = $rule->approvalRuleSteps()->pluck('role_name')->filter()->unique()->values();

        if ($roles->isEmpty()) {
            throw new RuntimeException('The matched approval rule has no configured approver roles.');
        }

        $record->cashRequestApprovals()->createMany(
            $roles->map(fn($role) => [
                'role_name' => $role,
                'status'    => 'pending',
            ])->all()
        );
    }

    public function filterPendingForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query->whereIn('status', [Status::PENDING->value, Status::IN_PROGRESS->value]);
        }

        $roles = $user->roles()->pluck('name')->all();

        if (empty($roles)) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereIn('status', [Status::PENDING->value, Status::IN_PROGRESS->value])
            ->whereExists(function ($subquery) use ($roles) {
                $subquery->selectRaw('1')
                    ->from('cash_request_approvals as cra')
                    ->whereColumn('cra.cash_request_id', 'cash_requests.id')
                    ->where('cra.status', 'pending')
                    ->whereIn('cra.role_name', $roles);
            });
    }

    public function userCanReview($record, User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->getPendingApprovalForUser($record, $user) !== null;
    }

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
                    'status'         => Status::IN_PROGRESS->value,
                    'status_remarks' => $this->resolveFinalApprovalRemark($record),
                ]);

                return [
                    'status_remarks' => $remark,
                    'is_final_step'  => false,
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

    private function getPendingApprovalForUser($record, User $user): ?CashRequestApproval
    {
        if ($user->isSuperAdmin()) {
            return $record->cashRequestApprovals()
                ->where('status', 'pending')
                ->first();
        }

        $roles = $user->roles()->pluck('name')->all();

        if (empty($roles)) {
            return null;
        }

        return $record->cashRequestApprovals()
            ->where('status', 'pending')
            ->whereIn('role_name', $roles)
            ->first();
    }

    private function approvedRemarkByRole(string $role): string
    {
        return match ($role) {
            'department_head'        => StatusRemarks::DEPARTMENT_HEAD_APPROVED_REQUEST->value,
            'president'              => StatusRemarks::PRESIDENT_APPROVED_REQUEST->value,
            'treasury_manager'       => StatusRemarks::TREASURY_MANAGER_APPROVED_REQUEST->value,
            'treasury_supervisor'    => StatusRemarks::TREASURY_SUPERVISOR_APPROVED_REQUEST->value,
            'sales_channel_manager'  => StatusRemarks::SALES_CHANNEL_MANAGER_APPROVED_REQUEST->value,
            'national_sales_manager' => StatusRemarks::NATIONAL_SALES_MANAGER_APPROVED_REQUEST->value,
            default                  => $this->fallbackRemark($role, 'Approved Request'),
        };
    }

    private function rejectedRemarkByRole(string $role): string
    {
        return match ($role) {
            'department_head'        => StatusRemarks::DEPARTMENT_HEAD_REJECTED_REQUEST->value,
            'president'              => StatusRemarks::PRESIDENT_REJECTED_REQUEST->value,
            'treasury_manager'       => StatusRemarks::TREASURY_MANAGER_REJECTED_REQUEST->value,
            'treasury_supervisor'    => StatusRemarks::TREASURY_SUPERVISOR_REJECTED_REQUEST->value,
            'sales_channel_manager'  => StatusRemarks::SALES_CHANNEL_MANAGER_REJECTED_REQUEST->value,
            'national_sales_manager' => StatusRemarks::NATIONAL_SALES_MANAGER_REJECTED_REQUEST->value,
            default                  => $this->fallbackRemark($role, 'Rejected Request'),
        };
    }

    private function fallbackRemark(string $role, string $suffix): string
    {
        return Str::of($role)->replace('_', ' ')->title()->append(' ', $suffix)->toString();
    }

    private function resolveFinalApprovalRemark($record): string
    {
        return match ($record->nature_of_request) {
            NatureOfRequestEnum::CASH_ADVANCE->value => StatusRemarks::FOR_FINANCE_VERIFICATION->value,
            default                                  => StatusRemarks::FOR_PAYMENT_PROCESSING->value,
        };
    }
}
