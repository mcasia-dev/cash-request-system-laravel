<?php

namespace App\Filament\Widgets;

use App\Enums\CashRequest\Status;
use App\Filament\Widgets\Concerns\HasDashboardReportLinks;
use App\Models\CashRequest\ForCashRelease;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ReleaseAmountSummaryStats extends ApexChartWidget
{
    use HasDashboardReportLinks;

    protected static ?string $chartId = 'releaseAmountSummaryStatsChart';

    protected static ?string $heading = 'Total Amount Released';
    protected static ?int $contentHeight = 320;
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasRole('treasury_staff') || $user->hasRole('treasury_manager');
    }

    protected function getSubheading(): null|string|Htmlable|\Illuminate\Contracts\View\View
    {
        return $this->renderReportLinks('release-amount-summary');
    }

    protected function getOptions(): array
    {
        $baseQuery = $this->getScopedReleasedQuery();

        $liquidatedAmount = (float)(clone $baseQuery)
            ->whereHas('cashRequest', fn(Builder $query): Builder => $query->where('status', Status::LIQUIDATED->value))
            ->sum('cash_requests.requesting_amount');

        $unliquidatedAmount = (float)(clone $baseQuery)
            ->whereHas('cashRequest', fn(Builder $query): Builder => $query->where('status', Status::RELEASED->value))
            ->sum('cash_requests.requesting_amount');

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
            'title' => [
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

        return $user->isSuperAdmin() || $user->hasRole('treasury_manager');
    }

    private function formatCurrency(float|int|string|null $amount): string
    {
        return 'PHP ' . number_format((float)$amount, 2);
    }
}
