<?php

namespace App\Services\Reports;

use App\Models\CashRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class TopActiveUsersReportService
{
    public function getReportData(): array
    {
        $topSubmitters = CashRequest::query()
            ->join('users', 'users.id', '=', 'cash_requests.user_id')
            ->selectRaw('users.name, users.position, COUNT(cash_requests.id) as total')
            ->groupBy('users.name', 'users.position')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $topApprovers = Activity::query()
            ->join('users', 'users.id', '=', 'activity_log.causer_id')
            ->where('activity_log.causer_type', User::class)
            ->whereIn('activity_log.event', ['approved', 'approved-for-liquidation'])
            ->selectRaw('users.name, users.position, COUNT(activity_log.id) as total')
            ->groupBy('users.name', 'users.position')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'generatedAt' => now(),
            'topSubmitters' => $this->normalizeRows($topSubmitters),
            'topApprovers' => $this->normalizeRows($topApprovers),
        ];
    }

    private function normalizeRows(Collection $rows): Collection
    {
        $max = (int) ($rows->max('total') ?? 0);

        return $rows->map(function ($row) use ($max): array {
            $total = (int) $row->total;
            $position = filled($row->position) ? (string) $row->position : 'N/A';

            return [
                'name' => (string) $row->name,
                'position' => $position,
                'label' => (string) $row->name . " ({$position})",
                'total' => $total,
                'width' => $max > 0 ? max(12, (int) round(($total / $max) * 100)) : 0,
            ];
        })->values();
    }
}
