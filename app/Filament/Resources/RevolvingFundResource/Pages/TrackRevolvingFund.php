<?php

namespace App\Filament\Resources\RevolvingFundResource\Pages;

use App\Enums\RevolvingFund\Status;
use App\Enums\RevolvingFund\StatusRemarks;
use App\Filament\Resources\RevolvingFundResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;

class TrackRevolvingFund extends ViewRecord
{
    protected static string $resource = RevolvingFundResource::class;
    protected static string $view = 'filament.resources.revolving-fund-resource.pages.track-revolving-fund-status';

    private const DISPLAY_TIMEZONE = 'Asia/Manila';

    public function getHeading(): string
    {
        return 'Revolving Fund Status Tracker';
    }

    public function getTrackerSteps(): array
    {
        $record = $this->getRecord()->loadMissing([
            'addedBy',
            'disbursementAddedBy',
            'releasedBy',
            'revolvingFundApprovals.approver',
        ]);

        $submittedStep = [
            'title' => 'Request Submitted',
            'status' => 'approved',
            'statusLabel' => 'Submitted',
            'remarks' => $record->status_remarks ?: 'Revolving fund submitted',
            'by' => $record->addedBy?->name ?? 'N/A',
            'date' => $record->created_at?->setTimezone(self::DISPLAY_TIMEZONE)->format('F d, Y h:i A') ?? 'N/A',
        ];

        $approvals = $record->revolvingFundApprovals
            ->sortBy('step_order')
            ->values();

        $hasDeclinedBefore = false;
        $currentPendingId = $approvals
            ->firstWhere('status', 'pending')
            ?->id;

        $approvalSteps = [];

        foreach ($approvals as $approval) {
            $roleTitle = Str::of($approval->role_name ?? 'Role')
                ->replace('_', ' ')
                ->title()
                ->toString();

            $title = "Step {$approval->step_order} - {$roleTitle}";

            if ($hasDeclinedBefore) {
                $approvalSteps[] = [
                    'title' => $title,
                    'status' => 'stopped',
                    'statusLabel' => 'Stopped',
                    'remarks' => 'Process stopped due to previous rejection.',
                    'by' => 'N/A',
                    'date' => 'N/A',
                ];

                continue;
            }

            if ($approval->status === 'approved') {
                $approvalSteps[] = [
                    'title' => $title,
                    'status' => 'approved',
                    'statusLabel' => 'Approved',
                    'remarks' => "{$roleTitle} approved this step.",
                    'by' => $approval->approver?->name ?? 'N/A',
                    'date' => $approval->acted_at?->setTimezone(self::DISPLAY_TIMEZONE)->format('F d, Y h:i A') ?? 'N/A',
                ];

                continue;
            }

            if (in_array($approval->status, ['declined', Status::REJECTED->value], true)) {
                $approvalSteps[] = [
                    'title' => $title,
                    'status' => 'rejected',
                    'statusLabel' => 'Rejected',
                    'remarks' => $record->status_remarks ?: "{$roleTitle} rejected this step.",
                    'by' => $approval->approver?->name ?? 'N/A',
                    'date' => $approval->acted_at?->setTimezone(self::DISPLAY_TIMEZONE)->format('F d, Y h:i A') ?? 'N/A',
                ];

                $hasDeclinedBefore = true;

                continue;
            }

            $isCurrentPending = $approval->id === $currentPendingId;

            $approvalSteps[] = [
                'title' => $title,
                'status' => $isCurrentPending ? 'pending' : 'upcoming',
                'statusLabel' => $isCurrentPending ? 'Pending' : 'Not yet started',
                'remarks' => $isCurrentPending
                    ? 'Waiting for current approver action.'
                    : 'Waiting for previous step completion.',
                'by' => 'N/A',
                'date' => 'N/A',
            ];
        }

        $treasurySteps = $this->buildTreasurySteps($record);

        return [$submittedStep, ...$approvalSteps, ...$treasurySteps];
    }

    private function buildTreasurySteps($record): array
    {
        $hasDeclinedApproval = $record->revolvingFundApprovals
            ->where('status', 'declined')
            ->isNotEmpty();

        $hasPendingApproval = $record->revolvingFundApprovals
            ->where('status', 'pending')
            ->isNotEmpty();

        $isTreasuryRejected = $record->status_remarks === StatusRemarks::TREASURY_REJECTED->value;
        $isForPaymentProcessing = $record->status_remarks === StatusRemarks::FOR_PAYMENT_PROCESSING->value;
        $isForReleasing = $record->status_remarks === StatusRemarks::FOR_RELEASING->value;
        $isReleased = $record->status_remarks === StatusRemarks::FUND_RELEASED->value;

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
                'remarks' => $record->remarks ?: 'Treasury rejected this request.',
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
                'remarks' => 'Revolving fund released.',
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
