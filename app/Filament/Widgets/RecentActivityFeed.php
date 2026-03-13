<?php

namespace App\Filament\Widgets;

use App\Enums\CashRequest\StatusRemarks;
use App\Models\CashRequest\CashRequest;
use App\Models\CashRequest\PaymentProcess;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class RecentActivityFeed extends Widget
{
    protected static string $view = 'filament.widgets.recent-activity-feed';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 7;

    public static function canView(): bool
    {
        $user = Auth::user();

        return (bool)$user?->isSuperAdmin();
    }

    protected function getViewData(): array
    {
        $activities = Activity::query()
            ->with('causer', 'subject')
            ->where(function ($query): void {
                $query
                    ->where(function ($subQuery): void {
                        $subQuery
                            ->where('subject_type', CashRequest::class)
                            ->whereIn('event', ['created', 'rejected', 'liquidated']);
                    })
                    ->orWhere(function ($subQuery): void {
                        $subQuery
                            ->where('subject_type', PaymentProcess::class)
                            ->whereIn('event', ['approved', 'rejected']);
                    });
            })
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function (Activity $activity): array {
                $requestNo = (string)($activity->properties['request_no'] ?? $activity->subject?->request_no ?? 'N/A');
                $activityName = (string)($activity->properties['activity_name'] ?? $activity->subject?->activity_name ?? 'Cash request');

                return [
                    'label' => $this->resolveLabel($activity),
                    'tone' => $this->resolveTone($activity),
                    'causer_name' => (string)($activity->causer?->name ?? 'System'),
                    'request_no' => $requestNo,
                    'activity_name' => $activityName,
                    'description' => (string)$activity->description,
                    'created_at_human' => $activity->created_at?->diffForHumans(),
                    'created_at_full' => $activity->created_at?->format('M d, Y h:i A'),
                ];
            });

        return ['activities' => $activities];
    }

    private function resolveLabel(Activity $activity): string
    {
        return match ($activity->event) {
            'created' => 'Request submitted',
            'approved', 'approved-for-liquidation' => 'Request approved',
            'rejected' => 'Request rejected',
            'liquidated' => $this->isLiquidationSubmission($activity)
                ? 'Reimbursement processed'
                : 'Reimbursement processed',
            default => 'Activity recorded',
        };
    }

    private function resolveTone(Activity $activity): string
    {
        return match ($activity->event) {
            'created' => 'info',
            'approved', 'approved-for-liquidation' => 'success',
            'rejected' => 'danger',
            'liquidated' => 'warning',
            default => 'gray',
        };
    }

    private function isLiquidationSubmission(Activity $activity): bool
    {
        return (string)($activity->properties['status_remarks'] ?? '') === StatusRemarks::LIQUIDATION_RECEIPT_SUBMITTED->value;
    }
}
