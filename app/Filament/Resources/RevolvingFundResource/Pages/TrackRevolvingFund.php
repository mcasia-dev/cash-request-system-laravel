<?php

namespace App\Filament\Resources\RevolvingFundResource\Pages;

use App\Enums\RevolvingFund\Status;
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

        return [$submittedStep, ...$approvalSteps];
    }
}
