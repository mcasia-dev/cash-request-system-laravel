<?php

namespace App\Filament\Widgets;

use App\Enums\NatureOfRequestEnum;
use App\Models\ForCashRelease;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class MyReleaseNaturePercentageChart extends ApexChartWidget
{
    protected static ?string $chartId = 'myReleaseNaturePercentageChart';

    protected static ?string $heading = 'Release Percentage by Type';
    protected static ?int $contentHeight = 320;
    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 1];
    protected static ?int $sort = 4;

    protected function getFilters(): ?array
    {
        return [
            'day' => 'Today',
            'week' => 'This Week',
            'month' => 'This Month',
            'quarter' => 'This Quarter',
            'year' => 'This Year',
        ];
    }

    protected function getOptions(): array
    {
        $user = Auth::user();

        if (!$user) {
            return $this->buildChartOptions(0, 0, 0);
        }

        [$start, $end] = $this->resolveDateRange((string)($this->filter ?? 'month'));

        $baseQuery = ForCashRelease::query()
            ->whereNotNull('date_released')
            ->whereBetween('date_released', [$start, $end])
            ->whereHas('cashRequest', function (Builder $query): void {
                $query->whereIn('nature_of_request', [
                    NatureOfRequestEnum::CASH_ADVANCE->value,
                    NatureOfRequestEnum::PETTY_CASH->value,
                ]);
            });

        if (!$this->canSummarizeAllData()) {
            $baseQuery->where('released_by', $user->id);
        }

        $cashAdvanceCount = (clone $baseQuery)
            ->whereHas('cashRequest', function (Builder $query): void {
                $query->where('nature_of_request', NatureOfRequestEnum::CASH_ADVANCE->value);
            })
            ->distinct()
            ->count('cash_request_id');

        $pettyCashCount = (clone $baseQuery)
            ->whereHas('cashRequest', function (Builder $query): void {
                $query->where('nature_of_request', NatureOfRequestEnum::PETTY_CASH->value);
            })
            ->distinct()
            ->count('cash_request_id');

        $total = $cashAdvanceCount + $pettyCashCount;

        $cashAdvancePercent = $total > 0 ? round(($cashAdvanceCount / $total) * 100, 2) : 0;
        $pettyCashPercent = $total > 0 ? round(($pettyCashCount / $total) * 100, 2) : 0;

        return $this->buildChartOptions($cashAdvancePercent, $pettyCashPercent, $total);
    }

    private function canSummarizeAllData(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasRole('admin');
    }

    private function resolveDateRange(string $filter): array
    {
        $now = now();

        return match ($filter) {
            'day' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(Carbon::MONDAY), $now->copy()->endOfWeek(Carbon::SUNDAY)],
            'quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    private function buildChartOptions(float|int $cashAdvancePercent, float|int $pettyCashPercent, int $total): array
    {
        return [
            'chart' => [
                'type' => 'bar',
                'height' => 300,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => [
                [
                    'name' => 'Release Percentage',
                    'data' => [$cashAdvancePercent, $pettyCashPercent],
                ],
            ],
            'xaxis' => [
                'categories' => ['Cash Advance', 'Petty Cash'],
                'axisBorder' => [
                    'show' => true,
                    'color' => '#d1d5db',
                ],
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'colors' => ['#6b7280', '#6b7280'],
                    ],
                ],
            ],
            'yaxis' => [
                'min' => 0,
                'max' => 100,
                'tickAmount' => 5,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                        'colors' => ['#6b7280'],
                    ],
                ],
            ],
            'grid' => [
                'borderColor' => '#e5e7eb',
                'strokeDashArray' => 4,
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'colors' => ['#3b82f6', '#f59e0b'],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'distributed' => true,
                    'borderRadius' => 4,
                    'columnWidth' => '55%',
                ],
            ],
            'tooltip' => [
                'theme' => 'light',
            ],
            'subtitle' => [
                'text' => 'Total released requests: ' . $total,
                'align' => 'left',
                'style' => [
                    'fontSize' => '12px',
                    'color' => '#6b7280',
                ],
            ],
            'noData' => [
                'text' => 'No release data in selected period.',
            ],
            'responsive' => [
                [
                    'breakpoint' => 640,
                    'options' => [
                        'chart' => [
                            'height' => 240,
                        ],
                        'plotOptions' => [
                            'bar' => [
                                'columnWidth' => '65%',
                            ],
                        ],
                        'xaxis' => [
                            'labels' => [
                                'style' => [
                                    'fontSize' => '11px',
                                ],
                            ],
                        ],
                        'yaxis' => [
                            'labels' => [
                                'style' => [
                                    'fontSize' => '11px',
                                ],
                            ],
                        ],
                        'legend' => [
                            'fontSize' => '11px',
                        ],
                    ],
                ],
            ],
        ];
    }
}
