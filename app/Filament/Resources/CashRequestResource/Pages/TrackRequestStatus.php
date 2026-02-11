<?php
namespace App\Filament\Resources\CashRequestResource\Pages;

use App\Enums\CashRequest\StatusRemarks;
use App\Enums\NatureOfRequestEnum;
use App\Filament\Resources\CashRequestResource;
use App\Models\CashRequest;
use Filament\Resources\Pages\ViewRecord;
use Spatie\Activitylog\Models\Activity;

class TrackRequestStatus extends ViewRecord
{
    protected static string $resource = CashRequestResource::class;
    protected static string $view     = 'filament.resources.cash-request-resource.pages.track-request-status';

    public function getHeading(): string
    {
        return 'Request Status Tracker';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTrackerSteps(): array
    {
        $record = $this->getRecord();

        return [
            $this->buildSubmittedStep($record),
            $this->buildDepartmentHeadStep($record),
            $this->buildTreasuryStep($record),
        ];
    }

    public function isPettyCash(): bool
    {
        return $this->getRecord()->nature_of_request === NatureOfRequestEnum::PETTY_CASH->value;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSubmittedStep(CashRequest $record): array
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
     * @return array<string, mixed>
     */
    private function buildDepartmentHeadStep(CashRequest $record): array
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
     * @return array<string, mixed>
     */
    private function buildTreasuryStep(CashRequest $record): array
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
     * @param array<int, string> $remarks
     */
    private function getLatestActivityByRemarks(CashRequest $record, array $remarks): ?Activity
    {
        foreach ($remarks as $remark) {
            $activity = Activity::query()
                ->where('subject_type', CashRequest::class)
                ->where('subject_id', $record->id)
                ->where('properties->status_remarks', $remark)
                ->latest('created_at')
                ->with('causer')
                ->first();

            if ($activity) {
                return $activity;
            }
        }

        return null;
    }

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
}
