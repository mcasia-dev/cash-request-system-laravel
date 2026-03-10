<?php

namespace App\Filament\Widgets;

use App\Enums\CashRequest\Status;
use App\Filament\Widgets\Concerns\HasDashboardReportLinks;
use App\Models\CashRequest;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RequestsByStatusChart extends ApexChartWidget
{
    use HasDashboardReportLinks;

    protected static ?string $chartId = 'requestsByStatusChart';

    protected static ?string $heading = 'Requests by Status';
    protected static ?int $contentHeight = 320;
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $user = Auth::user();

        return (bool)$user?->isSuperAdmin();
    }

    protected function getSubheading(): null|string|Htmlable|\Illuminate\Contracts\View\View
    {
        return $this->renderReportLinks('requests-by-status');
    }

    protected function getOptions(): array
    {
        $pendingCount = CashRequest::query()
            ->where('status', Status::PENDING->value)
            ->count();

        $forApprovalCount = CashRequest::query()
            ->where('status', Status::IN_PROGRESS->value)
            ->count();

        $approvedCount = CashRequest::query()
            ->where('status', Status::APPROVED->value)
            ->count();

        $rejectedCount = CashRequest::query()
            ->where('status', Status::REJECTED->value)
            ->count();

        return [
            'chart' => [
                'type' => 'polarArea',
                'height' => 300,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => [$pendingCount, $forApprovalCount, $approvedCount, $rejectedCount],
            'labels' => ['Pending', 'For Approval', 'Approved', 'Rejected'],
            'colors' => ['#f59e0b', '#3b82f6', '#22c55e', '#ef4444'],
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
            'tooltip' => [
                'y' => [
                    'formatter' => 'function (val) { return Number(val).toLocaleString("en-US"); }',
                ],
            ],
            'title' => [
                'text' => 'Overview of current request statuses',
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
