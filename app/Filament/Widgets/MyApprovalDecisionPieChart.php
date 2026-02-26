<?php

namespace App\Filament\Widgets;

use App\Models\CashRequestApproval;
use Illuminate\Support\Facades\Auth;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class MyApprovalDecisionPieChart extends ApexChartWidget
{
    protected static ?string $chartId = 'myApprovalDecisionPieChart';

    protected static ?string $heading = 'Approved/Rejected Requests';
    protected static ?int $contentHeight = 320;
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];
    protected static ?int $sort = 5;

    protected function getOptions(): array
    {
        $user = Auth::user();

        if (!$user) {
            return $this->buildChartOptions(0, 0);
        }

        $baseQuery = CashRequestApproval::query();

        if (!$this->canSummarizeAllData()) {
            $baseQuery->where('approved_by', (string)$user->id);
        }

        $approvedCount = (clone $baseQuery)
            ->where('status', 'approved')
            ->distinct()
            ->count('cash_request_id');

        $rejectedCount = (clone $baseQuery)
            ->where('status', 'declined')
            ->distinct()
            ->count('cash_request_id');

        return $this->buildChartOptions($approvedCount, $rejectedCount);
    }

    private function canSummarizeAllData(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasRole('admin');
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
