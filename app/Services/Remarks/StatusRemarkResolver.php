<?php

namespace App\Services\Remarks;

use App\Enums\CashRequest\StatusRemarks;
use App\Models\User;
use Illuminate\Support\Str;

class StatusRemarkResolver
{
    public function approveByPermissions(User $user, string $context = 'approval'): string
    {
        $remark = $this->firstPermissionMatch($user, $this->getPermissionMap($context, 'approve_permissions'));

        return $remark ?? $this->defaultFor('approved');
    }

    public function approveByPermissionsOrNoPermissions(User $user, string $context = 'approval'): string
    {
        $remark = $this->firstPermissionMatch($user, $this->getPermissionMap($context, 'approve_permissions'));

        return $remark ?? $this->defaultFor('no_permissions');
    }

    public function rejectByPermissions(User $user, string $context = 'approval'): string
    {
        $remark = $this->firstPermissionMatch($user, $this->getPermissionMap($context, 'reject_permissions'));

        return $remark ?? $this->defaultFor('rejected');
    }

    public function rejectByPermissionsOrNoPermissions(User $user, string $context = 'approval'): string
    {
        $remark = $this->firstPermissionMatch($user, $this->getPermissionMap($context, 'reject_permissions'));

        return $remark ?? $this->defaultFor('no_permissions');
    }

    public function releaseByPermissions(User $user): string
    {
        $remark = $this->firstPermissionMatch($user, $this->getPermissionMap('release', 'release_permissions'));

        return $remark ?? $this->defaultFor('released');
    }

    public function approveByRole(string $role): string
    {
        $map = config('approval_remarks.role_approve', []);

        return $map[$role] ?? $this->fallbackRemark($role, 'Approved Request');
    }

    public function rejectByRole(string $role): string
    {
        $map = config('approval_remarks.role_reject', []);

        return $map[$role] ?? $this->fallbackRemark($role, 'Rejected Request');
    }

    public function finalRemarkForNature(string $nature): string
    {
        $map = config('approval_remarks.final_by_nature', []);

        return $map[$nature] ?? ($map['*'] ?? StatusRemarks::FOR_PAYMENT_PROCESSING->value);
    }

    public function noPermissions(): string
    {
        return $this->defaultFor('no_permissions');
    }

    private function firstPermissionMatch(User $user, array $map): ?string
    {
        foreach ($map as $permission => $remark) {
            if ($user->can($permission)) {
                return $remark;
            }
        }

        return null;
    }

    private function getPermissionMap(string $context, string $key): array
    {
        return config("approval_remarks.contexts.{$context}.{$key}", []);
    }

    private function defaultFor(string $key): string
    {
        return config("approval_remarks.defaults.{$key}") ?? '';
    }

    private function fallbackRemark(string $role, string $suffix): string
    {
        return Str::of($role)->replace('_', ' ')->title()->append(' ', $suffix)->toString();
    }
}
