<?php

namespace App\Filament\Resources\ReimbursementResource\Pages;

use App\Enums\Reimbursement\StatusRemarks;
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
        $record = $this->getRecord()->loadMissing([
            'payee',
            'reimbursementApprovals.approver',
            'disbursementAddedBy',
            'releasedBy',
        ]);

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

        $treasurySteps = $this->buildTreasurySteps($record);

        return [$submittedStep, ...$approvalSteps, ...$treasurySteps];
    }

    private function buildTreasurySteps($record): array
    {
        $hasDeclinedApproval = $record->reimbursementApprovals
            ->where('status', 'declined')
            ->isNotEmpty();

        $hasPendingApproval = $record->reimbursementApprovals
            ->where('status', 'pending')
            ->isNotEmpty();

        $isTreasuryRejected = $record->status_remarks === StatusRemarks::TREASURY_REJECTED->value;
        $isForPaymentProcessing = $record->status_remarks === StatusRemarks::FOR_PAYMENT_PROCESSING->value;
        $isForReleasing = $record->status_remarks === StatusRemarks::FOR_RELEASING->value;
        $isReleased = $record->status_remarks === StatusRemarks::REIMBURSEMENT_RELEASED->value;

        $treasuryProcessingStep = [
            'title' => 'Treasury Processing',
            'status' => 'upcoming',
            'statusLabel' => 'Not yet started',
            'remarks' => 'Waiting for approval completion.',
            'by' => 'N/A',
            'date' => 'N/A',
        ];

        if ($hasDeclinedApproval) {
            $treasuryProcessingStep = [
                'title' => 'Treasury Processing',
                'status' => 'stopped',
                'statusLabel' => 'Stopped',
                'remarks' => 'Process stopped due to previous approval rejection.',
                'by' => 'N/A',
                'date' => 'N/A',
            ];
        } elseif ($isTreasuryRejected) {
            $treasuryProcessingStep = [
                'title' => 'Treasury Processing',
                'status' => 'rejected',
                'statusLabel' => 'Rejected',
                'remarks' => $record->reason_for_rejection ?: 'Treasury rejected this request.',
                'by' => $record->disbursementAddedBy?->name ?? 'N/A',
                'date' => 'N/A',
            ];
        } elseif ($isForReleasing || $isReleased) {
            $treasuryProcessingStep = [
                'title' => 'Treasury Processing',
                'status' => 'approved',
                'statusLabel' => 'Approved',
                'remarks' => 'Treasury completed payment processing.',
                'by' => $record->disbursementAddedBy?->name ?? 'N/A',
                'date' => 'N/A',
            ];
        } elseif ($isForPaymentProcessing) {
            $treasuryProcessingStep = [
                'title' => 'Treasury Processing',
                'status' => 'pending',
                'statusLabel' => 'Pending',
                'remarks' => 'In treasury queue for payment processing.',
                'by' => 'N/A',
                'date' => 'N/A',
            ];
        } elseif ($hasPendingApproval) {
            $treasuryProcessingStep = [
                'title' => 'Treasury Processing',
                'status' => 'upcoming',
                'statusLabel' => 'Not yet started',
                'remarks' => 'Waiting for previous step completion.',
                'by' => 'N/A',
                'date' => 'N/A',
            ];
        }

        $treasuryReleaseStep = [
            'title' => 'Treasury Releasing',
            'status' => 'upcoming',
            'statusLabel' => 'Not yet started',
            'remarks' => 'Waiting for treasury processing.',
            'by' => 'N/A',
            'date' => 'N/A',
        ];

        if ($hasDeclinedApproval || $isTreasuryRejected) {
            $treasuryReleaseStep = [
                'title' => 'Treasury Releasing',
                'status' => 'stopped',
                'statusLabel' => 'Stopped',
                'remarks' => $isTreasuryRejected
                    ? 'Process stopped due to treasury rejection.'
                    : 'Process stopped due to previous approval rejection.',
                'by' => 'N/A',
                'date' => 'N/A',
            ];
        } elseif ($isReleased) {
            $treasuryReleaseStep = [
                'title' => 'Treasury Releasing',
                'status' => 'approved',
                'statusLabel' => 'Released',
                'remarks' => 'Reimbursement released.',
                'by' => $record->releasedBy?->name ?? 'N/A',
                'date' => $record->released_at?->setTimezone(self::DISPLAY_TIMEZONE)->format('F d, Y h:i A') ?? 'N/A',
            ];
        } elseif ($isForReleasing) {
            $treasuryReleaseStep = [
                'title' => 'Treasury Releasing',
                'status' => 'pending',
                'statusLabel' => 'Pending',
                'remarks' => 'Waiting for treasury release action.',
                'by' => 'N/A',
                'date' => 'N/A',
            ];
        }

        return [$treasuryProcessingStep, $treasuryReleaseStep];
    }
}
