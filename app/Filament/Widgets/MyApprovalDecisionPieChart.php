<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardReportLinks;
use App\Models\PaymentProcess;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Spatie\Activitylog\Models\Activity;

class MyApprovalDecisionPieChart extends ApexChartWidget
{
    use HasDashboardReportLinks;

    protected static ?string $chartId = 'myApprovalDecisionPieChart';

    protected static ?string $heading = 'Approved/Rejected Requests';
    protected static ?int $contentHeight = 320;
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];
    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasRole('treasury_staff')
            || $user->hasRole('treasury_manager');
    }

    protected function getSubheading(): null|string|Htmlable|\Illuminate\Contracts\View\View
    {
        return $this->renderReportLinks('approval-decision');
    }

    protected function getOptions(): array
    {
        $user = Auth::user();

        if (!$user) {
            return $this->buildChartOptions(0, 0);
        }

        $baseQuery = Activity::query()
            ->where('subject_type', PaymentProcess::class)
            ->whereIn('event', ['approved', 'rejected']);

        if (!$this->canSummarizeAllData()) {
            $baseQuery->causedBy($user);
        }

        $approvedCount = (clone $baseQuery)
            ->where('event', 'approved')
            ->distinct()
            ->count('subject_id');

        $rejectedCount = (clone $baseQuery)
            ->where('event', 'rejected')
            ->distinct()
            ->count('subject_id');

        return $this->buildChartOptions($approvedCount, $rejectedCount);
    }

    private function canSummarizeAllData(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasRole('treasury_manager');
    }

    private function buildChartOptions(int $approvedCount, int $rejectedCount): array
    {
        return [
            'chart' => [
                'type' => 'pie',
                'height' => 300,
            ],
            'series' => [$approvedCount, $rejectedCount],
            'labels' => ['Approved', 'Rejected'],
            'colors' => ['#22c55e', '#ef4444'],
            'legend' => [
                'position' => 'bottom',
                'fontFamily' => 'inherit',
            ],
            'stroke' => [
                'width' => 2,
                'colors' => ['#ffffff'],
            ],
            'dataLabels' => [
                'enabled' => true,
            ],
            'title' => [
                'text' => 'Requests by Status',
                'align' => 'left',
                'style' => [
                    'fontSize' => '12px',
                ],
            ],
            'responsive' => [
                [
                    'breakpoint' => 640,
                    'options' => [
                        'chart' => [
                            'height' => 240,
                        ],
                        'legend' => [
                            'position' => 'bottom',
                            'fontSize' => '11px',
                        ],
                    ],
                ],
            ],
        ];
    }
}
