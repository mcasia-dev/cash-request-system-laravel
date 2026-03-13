<?php

namespace App\Filament\Resources\ReimbursementResource\Pages;

use App\Filament\Resources\ReimbursementResource;
use Filament\Resources\Pages\ViewRecord;

class TrackReimbursementStatus extends ViewRecord
{
    protected static string $resource = ReimbursementResource::class;
    protected static string $view = 'filament.resources.reimbursement-resource.pages.track-reimbursement-status';
    private const DISPLAY_TIMEZONE = 'Asia/Manila';

    public function getHeading(): string
    {
        return 'Reimbursement Status Tracker';
    }

    public function getTrackerSteps(): array
    {
        $record = $this->getRecord()->loadMissing(['reimbursementApprovals.approver']);

        $submittedStep = [
            'title' => 'Request Submitted',
            'status' => 'approved',
            'statusLabel' => 'Submitted',
            'remarks' => $record->status_remarks ?: 'Reimbursement Submitted',
            'by' => $record->payee?->name ?? 'N/A',
            'date' => $record->created_at?->setTimezone(self::DISPLAY_TIMEZONE)->format('F d, Y h:i A') ?? 'N/A',
        ];

        $approvalSteps = $record->reimbursementApprovals
            ->map(function ($approval) use ($record) {
                $roleTitle = str($approval->role_name)->replace('_', ' ')->title()->toString();
                $departmentName = $approval->department?->department_name;
                $title = "Step {$approval->step_no} - {$roleTitle}" . ($departmentName ? " ({$departmentName})" : '');

                if ($approval->status === 'approved') {
                    return [
                        'title' => $title,
                        'status' => 'approved',
                        'statusLabel' => 'Approved',
                        'remarks' => "{$roleTitle} approved this step.",
                        'by' => $approval->approver?->name ?? 'N/A',
                        'date' => $approval->acted_at?->setTimezone(self::DISPLAY_TIMEZONE)->format('F d, Y h:i A') ?? 'N/A',
                    ];
                }

                if ($approval->status === 'declined') {
                    return [
                        'title' => $title,
                        'status' => 'rejected',
                        'statusLabel' => 'Rejected',
                        'remarks' => $record->reason_for_rejection ?: "{$roleTitle} rejected this step.",
                        'by' => $approval->approver?->name ?? 'N/A',
                        'date' => $approval->acted_at?->setTimezone(self::DISPLAY_TIMEZONE)->format('F d, Y h:i A') ?? 'N/A',
                    ];
                }

                $hasDeclinedBefore = $record->reimbursementApprovals
                    ->where('status', 'declined')
                    ->where('step_no', '<', $approval->step_no)
                    ->isNotEmpty();

                if ($hasDeclinedBefore) {
                    return [
                        'title' => $title,
                        'status' => 'stopped',
                        'statusLabel' => 'Stopped',
                        'remarks' => 'Process stopped due to previous rejection.',
                        'by' => 'N/A',
                        'date' => 'N/A',
                    ];
                }

                $isCurrentPending = $record->reimbursementApprovals
                        ->where('status', 'pending')
                        ->sortBy('step_no')
                        ->first()?->id === $approval->id;

                return [
                    'title' => $title,
                    'status' => $isCurrentPending ? 'pending' : 'upcoming',
                    'statusLabel' => $isCurrentPending ? 'Pending' : 'Not yet started',
                    'remarks' => $isCurrentPending ? 'Waiting for current approver action.' : 'Waiting for previous step completion.',
                    'by' => 'N/A',
                    'date' => 'N/A',
                ];
            })
            ->values()
            ->all();

        return [$submittedStep, ...$approvalSteps];
    }
}
