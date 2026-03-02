<?php

namespace App\Filament\Widgets;

use App\Enums\CashRequest\Status;
use App\Models\ForCashRelease;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReleaseAmountSummaryStats extends ApexChartWidget
{
    protected static ?string $chartId = 'releaseAmountSummaryStatsChart';

    protected static ?string $heading = 'Total Amount Released';
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];
    protected static ?int $contentHeight = 320;
    protected static ?int $sort = 3;

    protected function getOptions(): array
    {
        $baseQuery = $this->getScopedReleasedQuery();

        $liquidatedAmount = (float)(clone $baseQuery)
            ->whereHas('cashRequest', fn(Builder $query): Builder => $query->where('status', Status::LIQUIDATED->value))
            ->sum('requesting_amount');

        $unliquidatedAmount = (float)(clone $baseQuery)
            ->whereHas('cashRequest', fn(Builder $query): Builder => $query->where('status', Status::RELEASED->value))
            ->sum('requesting_amount');

        $totalReleasedAmount = $liquidatedAmount + $unliquidatedAmount;

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 300,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => [$liquidatedAmount, $unliquidatedAmount],
            'labels' => ['Liquidated', 'Unliquidated'],
            'colors' => ['#22c55e', '#f59e0b'],
            'legend' => [
                'position' => 'bottom',
                'fontSize' => '13px',
            ],
            'stroke' => [
                'width' => 4,
                'colors' => ['#ffffff'],
            ],
            'dataLabels' => [
                'enabled' => true,
            ],
            'tooltip' => [
                'y' => [
                    'formatter' => 'function (val) { return "PHP " + Number(val).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }',
                ],
            ],
            'plotOptions' => [
                'pie' => [
                    'donut' => [
                        'size' => '58%',
                    ],
                ],
            ],
            'subtitle' => [
                'text' => 'Total: ' . $this->formatCurrency($totalReleasedAmount),
                'align' => 'left',
                'style' => [
                    'fontSize' => '12px',
                ],
            ],
            'noData' => [
                'text' => 'No released amount data available.',
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
                        'plotOptions' => [
                            'pie' => [
                                'donut' => [
                                    'size' => '65%',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getScopedReleasedQuery(): Builder
    {
        $user = Auth::user();

        $query = ForCashRelease::query()
            ->join('cash_requests', 'cash_requests.id', '=', 'for_cash_releases.cash_request_id')
            ->whereIn('cash_requests.status', [
                Status::RELEASED->value,
                Status::LIQUIDATED->value,
            ]);

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if (!$this->canSummarizeAllData()) {
            $query->where('for_cash_releases.released_by', $user->id);
        }

        return $query;
    }

    private function canSummarizeAllData(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasRole('admin');
    }

    private function formatCurrency(float|int|string|null $amount): string
    {
        return 'PHP ' . number_format((float)$amount, 2);
    }
}
