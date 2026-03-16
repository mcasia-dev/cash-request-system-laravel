<?php

namespace App\Services\Reimbursement;

use App\Enums\CashRequest\Status;
use App\Enums\Reimbursement\StatusRemarks;
use App\Models\Reimbursement\ReimbursementApproval;
use App\Models\Reimbursement\ReimbursementModeApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use RuntimeException;

class ReimbursementApprovalFlowService
{
    public function initializeApprovals($record): void
    {
        if ($record->reimbursementApprovals()->exists()) {
            return;
        }

        $departmentId = $record->payee?->department_id;

        if (! $departmentId) {
            throw new RuntimeException('Requestor has no department assigned, so department head approval cannot be resolved.');
        }

        $modeSteps = ReimbursementModeApproval::query()
            ->where('reimbursement_mode_id', $record->reimbursement_mode_id)
            ->orderBy('step_no')
            ->get();

        if ($modeSteps->isEmpty()) {
            throw new RuntimeException('No reimbursement approval flow is configured for this mode of request.');
        }

        $normalizedModeSteps = $modeSteps
            ->filter(function ($step) use ($departmentId) {
                // Skip only duplicate "requestor's own department head" row,
                // since step 1 is injected automatically.
                return ! (
                    $step->role_name === 'department_head'
                    && (int) $step->department_id === (int) $departmentId
                );
            })
            ->values();

        $steps = collect([
            [
                'step_no' => 1,
                'department_id' => (int) $departmentId,
                'role_name' => 'department_head',
                'required' => true,
                'status' => 'pending',
                'assigned_user_ids' => null,
                'can_approve' => true,
                'can_reject' => true,
                'step_form_data' => null,
            ],
        ])->merge(
            $normalizedModeSteps->map(
                fn($step, $index) => [
                    'step_no' => $index + 2,
                    'department_id' => $step->department_id,
                    'role_name' => $step->role_name,
                    'required' => (bool) $step->required,
                    'status' => 'pending',
                    'assigned_user_ids' => ! empty($step->assigned_user_ids)
                        ? collect($step->assigned_user_ids)
                            ->map(fn($id) => (int) $id)
                            ->filter(fn($id) => $id > 0)
                            ->values()
                            ->all()
                        : null,
                    'can_approve' => (bool) ($step->can_approve ?? true),
                    'can_reject' => (bool) ($step->can_reject ?? true),
                    'step_form_data' => null,
                ]
            )
        );

        $record->reimbursementApprovals()->createMany(
            $steps->all()
        );
    }

    public function filterPendingForUser(Builder $query, User $user): Builder
    {
        $roleNames = $this->userRoleNames($user);

        if ($roleNames->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('status', [Status::PENDING->value, Status::IN_PROGRESS->value])
            ->whereHas('reimbursementApprovals', function (Builder $approvalQuery) use ($user, $roleNames) {
                $approvalQuery
                    ->where('status', 'pending')
                    ->whereIn('role_name', $roleNames->all())
                    ->where(function (Builder $assignedUserQuery) use ($user) {
                        $assignedUserQuery
                            ->whereNull('assigned_user_ids')
                            ->orWhereRaw('JSON_LENGTH(assigned_user_ids) = 0')
                            ->orWhere(function (Builder $jsonContainsQuery) use ($user) {
                                $jsonContainsQuery
                                    ->whereRaw('JSON_CONTAINS(assigned_user_ids, JSON_ARRAY(?))', [(int) $user->id])
                                    ->orWhereRaw('JSON_CONTAINS(assigned_user_ids, JSON_QUOTE(?))', [(string) $user->id]);
                            });
                    })
                    ->where(function (Builder $departmentQuery) use ($user) {
                        $departmentQuery
                            ->whereNull('department_id')
                            ->orWhere('department_id', $user->department_id);
                    })
                    ->whereRaw(
                        "reimbursement_approvals.id = (
                            SELECT ra2.id
                            FROM reimbursement_approvals ra2
                            WHERE ra2.reimbursement_id = reimbursement_approvals.reimbursement_id
                              AND ra2.status = ?
                            ORDER BY ra2.step_no ASC, ra2.id ASC
                            LIMIT 1
                        )",
                        ['pending']
                    );
            });
    }

    public function userCanReview($record, User $user): bool
    {
        return $this->getPendingApprovalForUser($record, $user) !== null;
    }

    public function applyApproval($record, User $user, array $stepFormData = []): array
    {
        $this->initializeApprovals($record);

        $approval = $this->getPendingApprovalForUser($record, $user);

        if (! $approval) {
            throw new RuntimeException('You are not the current approver for this reimbursement request.');
        }

        if (! (bool) $approval->can_approve) {
            throw new RuntimeException('This step is not allowed to approve requests.');
        }

        $approval->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'acted_at' => now(),
            'step_form_data' => $stepFormData ?: null,
        ]);

        $hasPending = $record->reimbursementApprovals()->where('status', 'pending')->exists();
        $roleTitle = $this->roleTitle($approval->role_name);

        if ($hasPending) {
            $record->update([
                'status' => Status::IN_PROGRESS->value,
                'status_remarks' => "{$roleTitle} Approved",
                'approved_by' => $user->id,
                'approved_at' => now(),
                'reason_for_rejection' => null,
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
            'approved_by' => $user->id,
            'approved_at' => now(),
            'reason_for_rejection' => null,
        ]);

        return [
            'is_final_step' => true,
            'status' => Status::IN_PROGRESS->value,
            'status_remarks' => StatusRemarks::FOR_PAYMENT_PROCESSING->value,
            'approved_role_name' => $approval->role_name,
        ];
    }

    public function applyRejection($record, User $user, string $reason, array $stepFormData = []): array
    {
        $this->initializeApprovals($record);

        $approval = $this->getPendingApprovalForUser($record, $user);

        if (! $approval) {
            throw new RuntimeException('You are not the current approver for this reimbursement request.');
        }

        if (! (bool) $approval->can_reject) {
            throw new RuntimeException('This step is not allowed to reject requests.');
        }

        $approval->update([
            'status' => 'declined',
            'approved_by' => $user->id,
            'acted_at' => now(),
            'step_form_data' => $stepFormData ?: null,
        ]);

        $roleTitle = $this->roleTitle($approval->role_name);
        $remarks = "{$roleTitle} Rejected";

        $record->update([
            'status' => Status::REJECTED->value,
            'status_remarks' => $remarks,
            'reason_for_rejection' => $reason,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return [
            'status' => Status::REJECTED->value,
            'status_remarks' => $remarks,
            'rejected_role_name' => $approval->role_name,
        ];
    }

    public function getCurrentPendingApprovers($record): Collection
    {
        $this->initializeApprovals($record);

        $current = $this->getCurrentPendingApproval($record);

        if (! $current) {
            return collect();
        }

        $query = User::query()->role($current->role_name);

        $assignedUserIds = collect($current->assigned_user_ids ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->values()
            ->all();

        if (! empty($assignedUserIds)) {
            $query->whereIn('id', $assignedUserIds);
        }

        if ($current->department_id) {
            $query->where('department_id', $current->department_id);
        }

        return $query->get();
    }

    private function getPendingApprovalForUser($record, User $user): ?ReimbursementApproval
    {
        $current = $this->getCurrentPendingApproval($record);

        if (! $current) {
            return null;
        }

        $roleNames = $this->userRoleNames($user);

        if (! $roleNames->contains($current->role_name)) {
            return null;
        }

        $assignedUserIds = collect($current->assigned_user_ids ?? [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->values()
            ->all();

        if (! empty($assignedUserIds) && ! in_array((int) $user->id, $assignedUserIds, true)) {
            return null;
        }

        if ($current->department_id !== null && (int) $user->department_id !== (int) $current->department_id) {
            return null;
        }

        return $current;
    }

    private function getCurrentPendingApproval($record): ?ReimbursementApproval
    {
        return $record->reimbursementApprovals()
            ->where('status', 'pending')
            ->orderBy('step_no')
            ->orderBy('id')
            ->first();
    }

    public function getCurrentStepConfiguration($record, User $user): ?array
    {
        $approval = $this->getPendingApprovalForUser($record, $user);

        if (! $approval) {
            return null;
        }

        return [
            'can_approve' => (bool) ($approval->can_approve ?? true),
            'can_reject' => (bool) ($approval->can_reject ?? true),
            'form_schema' => $this->resolveFormSchemaForStep($record, $approval),
        ];
    }

    private function userRoleNames(User $user): Collection
    {
        return $user->roles()
            ->pluck('name')
            ->map(fn($role) => (string) $role)
            ->values();
    }

    private function roleTitle(string $roleName): string
    {
        return str($roleName)->replace('_', ' ')->title()->toString();
    }

    private function resolveFormSchemaForStep($record, ReimbursementApproval $approval): array
    {
        if ($approval->step_no === 1 && $approval->role_name === 'department_head') {
            return [];
        }

        $departmentId = $record->payee?->department_id;

        $modeSteps = ReimbursementModeApproval::query()
            ->where('reimbursement_mode_id', $record->reimbursement_mode_id)
            ->orderBy('step_no')
            ->get();

        $normalizedModeSteps = $modeSteps
            ->filter(function ($step) use ($departmentId) {
                return ! (
                    $step->role_name === 'department_head'
                    && (int) $step->department_id === (int) $departmentId
                );
            })
            ->values();

        $index = max(((int) $approval->step_no) - 2, 0);
        $step = $normalizedModeSteps->get($index);

        return is_array($step?->form_schema) ? $step->form_schema : [];
    }
}
