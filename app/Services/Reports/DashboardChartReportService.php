<?php

namespace App\Services\Reports;

use App\Enums\CashRequest\NatureOfRequestEnum;
use App\Enums\CashRequest\Status;
use App\Models\CashRequest\CashRequest;
use App\Models\CashRequest\ForCashRelease;
use App\Models\CashRequest\PaymentProcess;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Spatie\Activitylog\Models\Activity;

class DashboardChartReportService
{
    public function getReport(string $report, ?User $user = null, ?string $filter = null): array
    {
        return match ($report) {
            'requests-by-status' => $this->requestsByStatus(),
            'release-amount-summary' => $this->releaseAmountSummary($user),
            'release-nature-percentage' => $this->releaseNaturePercentage($user, $filter ?? 'month'),
            'approval-decision' => $this->approvalDecision($user),
            default => throw new InvalidArgumentException("Unsupported dashboard report [{$report}]."),
        };
    }

    private function requestsByStatus(): array
    {
        $rows = [
            ['label' => 'Pending', 'value' => CashRequest::query()->where('status', Status::PENDING->value)->count()],
            ['label' => 'For Approval', 'value' => CashRequest::query()->where('status', Status::IN_PROGRESS->value)->count()],
            ['label' => 'Approved', 'value' => CashRequest::query()->where('status', Status::APPROVED->value)->count()],
            ['label' => 'Rejected', 'value' => CashRequest::query()->where('status', Status::REJECTED->value)->count()],
        ];

        return $this->formatReport(
            report: 'requests-by-status',
            title: 'Requests by Status',
            subtitle: 'Overview of current request statuses.',
            metricLabel: 'Requests',
            rows: $rows
        );
    }

    private function releaseAmountSummary(?User $user): array
    {
        $baseQuery = $this->getScopedReleasedQuery($user);

        $rows = [
            [
                'label' => 'Liquidated',
                'value' => (float)(clone $baseQuery)
                    ->whereHas('cashRequest', fn(Builder $query): Builder => $query->where('status', Status::LIQUIDATED->value))
                    ->sum('cash_requests.requesting_amount'),
            ],
            [
                'label' => 'Unliquidated',
                'value' => (float)(clone $baseQuery)
                    ->whereHas('cashRequest', fn(Builder $query): Builder => $query->where('status', Status::RELEASED->value))
                    ->sum('cash_requests.requesting_amount'),
            ],
        ];

        return $this->formatReport(
            report: 'release-amount-summary',
            title: 'Total Amount Released',
            subtitle: 'Released amount grouped by liquidation status.',
            metricLabel: 'Amount',
            rows: $rows,
            currency: true
        );
    }

    private function releaseNaturePercentage(?User $user, string $filter): array
    {
        [$start, $end] = $this->resolveDateRange($filter);

        $baseQuery = ForCashRelease::query()
            ->whereNotNull('date_released')
            ->whereBetween('date_released', [$start, $end])
            ->whereHas('cashRequest', function (Builder $query): void {
                $query->whereIn('nature_of_request', [
                    NatureOfRequestEnum::CASH_ADVANCE->value,
                    NatureOfRequestEnum::PETTY_CASH->value,
                ]);
            });

        if (!$this->canSummarizeAllReleaseData($user)) {
            $baseQuery->where('released_by', $user?->id);
        }

        $cashAdvanceCount = (clone $baseQuery)
            ->whereHas('cashRequest', fn(Builder $query): Builder => $query->where('nature_of_request', NatureOfRequestEnum::CASH_ADVANCE->value))
            ->distinct()
            ->count('cash_request_id');

        $pettyCashCount = (clone $baseQuery)
            ->whereHas('cashRequest', fn(Builder $query): Builder => $query->where('nature_of_request', NatureOfRequestEnum::PETTY_CASH->value))
            ->distinct()
            ->count('cash_request_id');

        $total = $cashAdvanceCount + $pettyCashCount;

        $rows = [
            ['label' => 'Cash Advance', 'value' => $total > 0 ? round(($cashAdvanceCount / $total) * 100, 2) : 0, 'raw_total' => $cashAdvanceCount],
            ['label' => 'Petty Cash', 'value' => $total > 0 ? round(($pettyCashCount / $total) * 100, 2) : 0, 'raw_total' => $pettyCashCount],
        ];

        return $this->formatReport(
            report: 'release-nature-percentage',
            title: 'Release Percentage by Type',
            subtitle: 'Release mix for ' . $this->humanizeFilter($filter) . '.',
            metricLabel: 'Percentage',
            rows: $rows,
            percentage: true,
            extra: ['filter' => $filter, 'total_requests' => $total]
        );
    }

    private function approvalDecision(?User $user): array
    {
        $baseQuery = Activity::query()
            ->where('subject_type', PaymentProcess::class)
            ->whereIn('event', ['approved', 'rejected']);

        if (!$this->canSummarizeAllApprovalData($user)) {
            $baseQuery->causedBy($user);
        }

        $rows = [
            ['label' => 'Approved', 'value' => (clone $baseQuery)->where('event', 'approved')->distinct()->count('subject_id')],
            ['label' => 'Rejected', 'value' => (clone $baseQuery)->where('event', 'rejected')->distinct()->count('subject_id')],
        ];

        return $this->formatReport(
            report: 'approval-decision',
            title: 'Approved/Rejected Requests',
            subtitle: 'Requests by treasury approval decision.',
            metricLabel: 'Requests',
            rows: $rows
        );
    }

    private function formatReport(
        string $report,
        string $title,
        string $subtitle,
        string $metricLabel,
        array  $rows,
        bool   $currency = false,
        bool   $percentage = false,
        array  $extra = []
    ): array
    {
        $max = max(array_map(fn(array $row): float|int => (float)$row['value'], $rows) ?: [0]);
        $total = array_sum(array_map(fn(array $row): float|int => (float)$row['value'], $rows));

        $normalizedRows = collect($rows)->map(function (array $row) use ($max, $currency, $percentage): array {
            $value = (float)$row['value'];

            return [
                ...$row,
                'width' => $max > 0 ? max(12, (int)round(($value / $max) * 100)) : 0,
                'formatted_value' => $currency
                    ? 'PHP ' . number_format($value, 2)
                    : ($percentage ? number_format($value, 2) . '%' : number_format((int)$value)),
            ];
        })->all();

        return [
            'report' => $report,
            'title' => $title,
            'subtitle' => $subtitle,
            'metricLabel' => $metricLabel,
            'rows' => $normalizedRows,
            'total' => $currency ? 'PHP ' . number_format($total, 2) : ($percentage ? number_format($total, 2) . '%' : number_format((int)$total)),
            'currency' => $currency,
            'percentage' => $percentage,
            'generatedAt' => now(),
            ...$extra,
        ];
    }

    private function getScopedReleasedQuery(?User $user): Builder
    {
        $query = ForCashRelease::query()
            ->join('cash_requests', 'cash_requests.id', '=', 'for_cash_releases.cash_request_id')
            ->whereIn('cash_requests.status', [
                Status::RELEASED->value,
                Status::LIQUIDATED->value,
            ]);

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if (!$this->canSummarizeAllReleaseData($user)) {
            $query->where('for_cash_releases.released_by', $user->id);
        }

        return $query;
    }

    private function canSummarizeAllReleaseData(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasRole('treasury_manager');
    }

    private function canSummarizeAllApprovalData(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasRole('treasury_manager');
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

    private function humanizeFilter(string $filter): string
    {
        return match ($filter) {
            'day' => 'Today',
            'week' => 'This Week',
            'quarter' => 'This Quarter',
            'year' => 'This Year',
            default => 'This Month',
        };
    }
}
