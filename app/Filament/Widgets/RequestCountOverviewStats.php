<?php

namespace App\Filament\Widgets;

use App\Enums\CashRequest\Status;
use App\Models\CashRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class RequestCountOverviewStats extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $baseQuery = $this->getScopedCashRequestQuery();

        $totalRequests = (clone $baseQuery)->count();
        $unliquidatedRequests = (clone $baseQuery)
            ->where('status', Status::RELEASED->value)
            ->count();
        $liquidatedRequests = (clone $baseQuery)
            ->where('status', Status::LIQUIDATED->value)
            ->count();
        $cancelledRequests = (clone $baseQuery)
            ->where('status', Status::CANCELLED->value)
            ->count();
        $rejectedRequests = (clone $baseQuery)
            ->where('status', Status::REJECTED->value)
            ->count();

        return [
            Stat::make('Total Requests', number_format($totalRequests))
                ->description('All submitted requests')
                ->color('info')
                ->chart($this->buildMonthlyTrend('all')),

            Stat::make('Unliquidated Requests', number_format($unliquidatedRequests))
                ->description('Status: Released')
                ->color('warning')
                ->chart($this->buildMonthlyTrend(Status::RELEASED->value)),

            Stat::make('Liquidated Requests', number_format($liquidatedRequests))
                ->description('Status: Liquidated')
                ->color('success')
                ->chart($this->buildMonthlyTrend(Status::LIQUIDATED->value)),

            Stat::make('Cancelled Requests', number_format($cancelledRequests))
                ->description('Status: Cancelled')
                ->color('gray')
                ->chart($this->buildMonthlyTrend(Status::CANCELLED->value)),

            Stat::make('Rejected Requests', number_format($rejectedRequests))
                ->description('Status: Rejected')
                ->color('danger')
                ->chart($this->buildMonthlyTrend(Status::REJECTED->value)),
        ];
    }

    private function getScopedCashRequestQuery(): Builder
    {
        $user = Auth::user();
        $query = CashRequest::query();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if (!$this->canSummarizeAllData()) {
            $query->where('user_id', $user->id);
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

    /**
     * Build a 12-month sparkline trend ending this month.
     *
     * @return array<int, int>
     */
    private function buildMonthlyTrend(string $scope): array
    {
        $trend = [];

        for ($offset = 11; $offset >= 0; $offset--) {
            $monthStart = Carbon::now()->subMonths($offset)->startOfMonth();
            $monthEnd = (clone $monthStart)->endOfMonth();

            $query = $this->getScopedCashRequestQuery()
                ->whereBetween('created_at', [$monthStart, $monthEnd]);

            if ($scope !== 'all') {
                $query->where('status', $scope);
            }

            $trend[] = $query->count();
        }

        return $trend;
    }
}
