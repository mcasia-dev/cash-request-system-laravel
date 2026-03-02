<?php
namespace App\Filament\Resources\CashRequestResource\Pages;

use App\Enums\CashRequest\StatusRemarks;
use App\Enums\NatureOfRequestEnum;
use App\Filament\Resources\CashRequestResource;
use App\Models\CashRequest;
use Filament\Resources\Pages\ViewRecord;
use Spatie\Activitylog\Models\Activity;

/**
 *
 */
class TrackRequestStatus extends ViewRecord
{
    protected static string $resource = CashRequestResource::class;
    protected static string $view     = 'filament.resources.cash-request-resource.pages.track-request-status';

    /**
     * Provide the page heading for the request status tracker.
     */
    public function getHeading(): string
    {
        return 'Request Status Tracker';
    }

    /**
     * Build the ordered tracker steps based on the request type.
     * @return array<int, array<string, mixed>>
     */
    public function getTrackerSteps(): array
    {
        $record = $this->getRecord();

        if ($this->isCashAdvance()) {
            return array_merge(
                [$this->buildSubmittedStep($record)],
                $this->buildCashAdvanceApprovalSteps($record),
                [
                    $this->buildFinanceStep($record),
                    $this->buildCashAdvanceTreasuryStep($record),
                ]
            );
        }

        return [
            $this->buildSubmittedStep($record),
            $this->buildDepartmentHeadStep($record),
            $this->buildPettyCashTreasuryStep($record),
        ];
    }

    /**
     * Check if the nature of request is petty cash.
     * @return bool
     */
    public function isPettyCash(): bool
    {
        return $this->getRecord()->nature_of_request === NatureOfRequestEnum::PETTY_CASH->value;
    }


    /**
     * Check if the nature of request is cash advance.
     * @return bool
     */
    public function isCashAdvance(): bool
    {
        return $this->getRecord()->nature_of_request === NatureOfRequestEnum::CASH_ADVANCE->value;
    }

    /**
     * Build the initial submitted step for the tracker.
     * @return array<string, mixed>
     */
    private function buildSubmittedStep($record): array
    {
        $submittedAt = $record->created_at;

        return [
            'title'       => 'Request Submitted',
            'status'      => 'approved',
            'statusLabel' => 'Submitted',
            'remarks'     => StatusRemarks::REQUEST_SUBMITTED->value,
            'by'          => $record->user?->name ?? 'N/A',
            'date'        => $submittedAt?->format('F d, Y h:i A') ?? 'N/A',
        ];
    }

    /**
     * Build the Department Head decision step using latest approval or rejection activity.
     * @return array<string, mixed>
     */
    private function buildDepartmentHeadStep($record): array
    {
        $approved = $this->getLatestActivityByRemarks($record, [
            StatusRemarks::DEPARTMENT_HEAD_APPROVED_REQUEST->value,
        ]);

        $rejected = $this->getLatestActivityByRemarks($record, [
            StatusRemarks::DEPARTMENT_HEAD_REJECTED_REQUEST->value,
        ]);

        if ($rejected) {
            return $this->makeStep('Department Head', 'rejected', 'Rejected', $rejected);
        }

        if ($approved) {
            return $this->makeStep('Department Head', 'approved', 'Approved', $approved);
        }

        return [
            'title'       => 'Department Head',
            'status'      => 'pending',
            'statusLabel' => 'Pending',
            'remarks'     => 'Waiting for review',
            'by'          => 'N/A',
            'date'        => 'N/A',
        ];
    }

    /**
     * Build the petty cash Treasury step, accounting for Department Head outcome and treasury queue state.
     * @return array<string, mixed>
     */
    private function buildPettyCashTreasuryStep($record): array
    {
        $deptHeadRejected = $this->getLatestActivityByRemarks($record, [
            StatusRemarks::DEPARTMENT_HEAD_REJECTED_REQUEST->value,
        ]);

        if ($deptHeadRejected) {
            return [
                'title'       => 'Treasury Department',
                'status'      => 'stopped',
                'statusLabel' => 'Stopped',
                'remarks'     => 'Process stopped due to Department Head rejection',
                'by'          => 'N/A',
                'date'        => 'N/A',
            ];
        }

        $approved = $this->getLatestActivityByRemarks($record, [
            StatusRemarks::TREASURY_MANAGER_APPROVED_REQUEST->value,
            StatusRemarks::TREASURY_SUPERVISOR_APPROVED_REQUEST->value,
        ]);

        $rejected = $this->getLatestActivityByRemarks($record, [
            StatusRemarks::TREASURY_MANAGER_REJECTED_REQUEST->value,
            StatusRemarks::TREASURY_SUPERVISOR_REJECTED_REQUEST->value,
        ]);

        if ($rejected) {
            return $this->makeStep('Treasury Department', 'rejected', 'Rejected', $rejected);
        }

        if ($approved) {
            return $this->makeStep('Treasury Department', 'approved', 'Approved', $approved);
        }

        $isInTreasuryQueue = in_array($record->status_remarks, [
            StatusRemarks::FOR_PAYMENT_PROCESSING->value,
            StatusRemarks::FOR_RELEASING->value,
        ], true);

        return [
            'title'       => 'Treasury Department',
            'status'      => $isInTreasuryQueue ? 'pending' : 'upcoming',
            'statusLabel' => $isInTreasuryQueue ? 'Pending' : 'Not yet started',
            'remarks'     => $isInTreasuryQueue ? 'In treasury queue for review' : 'Waiting for Department Head approval',
            'by'          => 'N/A',
            'date'        => 'N/A',
        ];
    }

    /**
     * Build the approval chain steps for cash advance requests.
     * @return array<int, array<string, mixed>>
     */
    private function buildCashAdvanceApprovalSteps($record): array
    {
        $approvals = $record->cashRequestApprovals()
            ->orderBy('id')
            ->get();

        if ($approvals->isEmpty()) {
            return [[
                'title'       => 'Approval',
                'status'      => $record->status === 'rejected' ? 'rejected' : 'pending',
                'statusLabel' => $record->status === 'rejected' ? 'Rejected' : 'Pending',
                'remarks'     => $record->status === 'rejected'
                    ? ($record->reason_for_rejection ?: 'Rejected during approval')
                    : 'Waiting for approver decision',
                'by'          => 'N/A',
                'date'        => 'N/A',
            ]];
        }

        $latestDeclined = $record->cashRequestApprovals()
            ->where('status', 'declined')
            ->orderByDesc('acted_at')
            ->orderByDesc('id')
            ->first();

        if ($latestDeclined) {
            $title = 'Approval - ' . str($latestDeclined->role_name)->replace('_', ' ')->title()->toString();

            return [[
                'title'       => $title,
                'status'      => 'rejected',
                'statusLabel' => 'Rejected',
                'remarks'     => $record->reason_for_rejection ?: $this->rejectedRemarkByRole($latestDeclined->role_name),
                'by'          => $this->resolveApproverName($latestDeclined->approved_by),
                'date'        => $latestDeclined->acted_at?->format('F d, Y h:i A') ?? 'N/A',
            ]];
        }

        $latestApproved = $record->cashRequestApprovals()
            ->where('status', 'approved')
            ->orderByDesc('acted_at')
            ->orderByDesc('id')
            ->first();

        if ($latestApproved) {
            $title = 'Approval - ' . str($latestApproved->role_name)->replace('_', ' ')->title()->toString();

            return [[
                'title'       => $title,
                'status'      => 'approved',
                'statusLabel' => 'Approved',
                'remarks'     => $this->approvedRemarkByRole($latestApproved->role_name),
                'by'          => $this->resolveApproverName($latestApproved->approved_by),
                'date'        => $latestApproved->acted_at?->format('F d, Y h:i A') ?? 'N/A',
            ]];
        }

        return [[
            'title'       => 'Approval',
            'status'      => 'pending',
            'statusLabel' => 'Pending',
            'remarks'     => 'Waiting for review',
            'by'          => 'N/A',
            'date'        => 'N/A',
        ]];
    }

    /**
     * Build the finance review step for cash advance requests.
     * @return array<string, mixed>
     */
    private function buildFinanceStep($record): array
    {
        $rejected = $this->getLatestActivityByRemarks($record, [
            StatusRemarks::FINANCE_DEPARTMENT_REJECTED_REQUEST->value,
        ]);

        $approved = $this->getLatestActivityByRemarks($record, [
            StatusRemarks::FINANCE_DEPARTMENT_APPROVED_REQUEST->value,
        ]);

        if ($rejected) {
            return $this->makeStep('Finance', 'rejected', 'Rejected', $rejected);
        }

        if ($approved) {
            return $this->makeStep('Finance', 'approved', 'Approved', $approved);
        }

        if ($this->hasDeclinedApprovalStep($record)) {
            return [
                'title'       => 'Finance',
                'status'      => 'stopped',
                'statusLabel' => 'Stopped',
                'remarks'     => 'Process stopped due to approval rejection',
                'by'          => 'N/A',
                'date'        => 'N/A',
            ];
        }

        if ($this->hasPendingApprovalStep($record)) {
            return [
                'title'       => 'Finance',
                'status'      => 'upcoming',
                'statusLabel' => 'Not yet started',
                'remarks'     => 'Waiting for approval completion',
                'by'          => 'N/A',
                'date'        => 'N/A',
            ];
        }

        $isInQueue = in_array($record->status_remarks, [
            StatusRemarks::FOR_FINANCE_VERIFICATION->value,
        ], true);

        return [
            'title'       => 'Finance',
            'status'      => $isInQueue ? 'pending' : 'upcoming',
            'statusLabel' => $isInQueue ? 'Pending' : 'Not yet started',
            'remarks'     => $isInQueue ? 'In finance verification queue' : 'Waiting for approval completion',
            'by'          => 'N/A',
            'date'        => 'N/A',
        ];
    }

    /**
     * Build the treasury processing step for cash advance requests.
     * @return array<string, mixed>
     */
    private function buildCashAdvanceTreasuryStep($record): array
    {
        $approved = $this->getLatestActivityByRemarks($record, [
            StatusRemarks::TREASURY_MANAGER_APPROVED_REQUEST->value,
            StatusRemarks::TREASURY_SUPERVISOR_APPROVED_REQUEST->value,
            StatusRemarks::TREASURY_MANAGER_RELEASED_CASH_REQUESTED->value,
            StatusRemarks::TREASURY_SUPERVISOR_RELEASED_CASH_REQUESTED->value,
        ]);

        $rejected = $this->getLatestActivityByRemarks($record, [
            StatusRemarks::TREASURY_MANAGER_REJECTED_REQUEST->value,
            StatusRemarks::TREASURY_SUPERVISOR_REJECTED_REQUEST->value,
        ]);

        if ($rejected) {
            return $this->makeStep('Treasury', 'rejected', 'Rejected', $rejected);
        }

        if ($approved) {
            return $this->makeStep('Treasury', 'approved', 'Approved', $approved);
        }

        $financeRejected = $this->getLatestActivityByRemarks($record, [
            StatusRemarks::FINANCE_DEPARTMENT_REJECTED_REQUEST->value,
        ]);

        if ($financeRejected) {
            return [
                'title'       => 'Treasury',
                'status'      => 'stopped',
                'statusLabel' => 'Stopped',
                'remarks'     => 'Process stopped due to finance rejection',
                'by'          => 'N/A',
                'date'        => 'N/A',
            ];
        }

        if ($this->hasDeclinedApprovalStep($record)) {
            return [
                'title'       => 'Treasury',
                'status'      => 'stopped',
                'statusLabel' => 'Stopped',
                'remarks'     => 'Process stopped due to approval rejection',
                'by'          => 'N/A',
                'date'        => 'N/A',
            ];
        }

        if ($this->hasPendingApprovalStep($record)) {
            return [
                'title'       => 'Treasury',
                'status'      => 'upcoming',
                'statusLabel' => 'Not yet started',
                'remarks'     => 'Waiting for approval completion',
                'by'          => 'N/A',
                'date'        => 'N/A',
            ];
        }

        $isInQueue = in_array($record->status_remarks, [
            StatusRemarks::FOR_PAYMENT_PROCESSING->value,
            StatusRemarks::FOR_RELEASING->value,
        ], true);

        return [
            'title'       => 'Treasury',
            'status'      => $isInQueue ? 'pending' : 'upcoming',
            'statusLabel' => $isInQueue ? 'Pending' : 'Not yet started',
            'remarks'     => $isInQueue ? 'In treasury queue for processing/releasing' : 'Waiting for finance approval',
            'by'          => 'N/A',
            'date'        => 'N/A',
        ];
    }

    /**
     * Determine whether any approval step has been rejected.
     */
    private function hasDeclinedApprovalStep($record): bool
    {
        return $record->cashRequestApprovals()->where('status', 'declined')->exists();
    }

    /**
     * Determine whether approval steps are still pending.
     */
    private function hasPendingApprovalStep($record): bool
    {
        return $record->cashRequestApprovals()->where('status', 'pending')->exists();
    }

    /**
     * Fetch the most recent activity for the given remarks on the request.
     * @param array<int, string> $remarks
     */
    private function getLatestActivityByRemarks($record, array $remarks): ?Activity
    {
        return Activity::query()
            ->where('subject_id', $record->id)
            ->whereIn('properties->status_remarks', $remarks)
            ->latest('created_at')
            ->with('causer')
            ->first();
    }

    /**
     * Convert an activity record into a standardized tracker step array.
     */
    private function makeStep(string $title, string $status, string $statusLabel, Activity $activity): array
    {
        return [
            'title'       => $title,
            'status'      => $status,
            'statusLabel' => $statusLabel,
            'remarks'     => $activity->properties['status_remarks'] ?? $statusLabel,
            'by'          => $activity->causer?->name ?? 'N/A',
            'date'        => $activity->created_at?->format('F d, Y h:i A') ?? 'N/A',
        ];
    }

    /**
     * Map a tracker state to its CSS class set.
     */
    public function getStateStyles(string $state): array
    {
        return match ($state) {
            'approved' => [
                'card'  => 'border-emerald-600',
                'title' => 'text-emerald-700',
                'badge' => 'bg-emerald-600 text-white',
            ],
            'rejected' => [
                'card'  => 'border-red-600',
                'title' => 'text-red-700',
                'badge' => 'bg-red-600 text-white',
            ],
            'pending'  => [
                'card'  => 'border-amber-500',
                'title' => 'text-amber-700',
                'badge' => 'bg-amber-500 text-white',
            ],
            default    => [
                'card'  => 'border-slate-300',
                'title' => 'text-slate-700',
                'badge' => 'bg-slate-300 text-slate-700',
            ],
        };
    }

    /**
     * Resolve the approved status remark string based on approver role.
     */
    private function approvedRemarkByRole(string $role): string
    {
        return match ($role) {
            'super_admin'            => StatusRemarks::SUPER_ADMIN_APPROVED_REQUEST->value,
            'department_head'        => StatusRemarks::DEPARTMENT_HEAD_APPROVED_REQUEST->value,
            'president'              => StatusRemarks::PRESIDENT_APPROVED_REQUEST->value,
            'treasury_manager'       => StatusRemarks::TREASURY_MANAGER_APPROVED_REQUEST->value,
            'treasury_supervisor'    => StatusRemarks::TREASURY_SUPERVISOR_APPROVED_REQUEST->value,
            'sales_channel_manager'  => StatusRemarks::SALES_CHANNEL_MANAGER_APPROVED_REQUEST->value,
            'national_sales_manager' => StatusRemarks::NATIONAL_SALES_MANAGER_APPROVED_REQUEST->value,
            default                  => str($role)->replace('_', ' ')->title()->append(' Approved Request')->toString(),
        };
    }

    /**
     * Resolve the rejected status remark string based on approver role.
     */
    private function rejectedRemarkByRole(string $role): string
    {
        return match ($role) {
            'super_admin'            => StatusRemarks::SUPER_ADMIN_REJECTED_REQUEST->value,
            'department_head'        => StatusRemarks::DEPARTMENT_HEAD_REJECTED_REQUEST->value,
            'president'              => StatusRemarks::PRESIDENT_REJECTED_REQUEST->value,
            'treasury_manager'       => StatusRemarks::TREASURY_MANAGER_REJECTED_REQUEST->value,
            'treasury_supervisor'    => StatusRemarks::TREASURY_SUPERVISOR_REJECTED_REQUEST->value,
            'sales_channel_manager'  => StatusRemarks::SALES_CHANNEL_MANAGER_REJECTED_REQUEST->value,
            'national_sales_manager' => StatusRemarks::NATIONAL_SALES_MANAGER_REJECTED_REQUEST->value,
            default                  => str($role)->replace('_', ' ')->title()->append(' Rejected Request')->toString(),
        };
    }

    /**
     * Look up the approver name by user id, returning N/A when missing.
     */
    private function resolveApproverName(?string $userId): string
    {
        if (! $userId) {
            return 'N/A';
        }

        return \App\Models\User::query()->find($userId)?->name ?? 'N/A';
    }
}
