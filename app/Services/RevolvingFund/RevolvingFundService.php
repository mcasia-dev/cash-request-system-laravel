<?php

namespace App\Services\RevolvingFund;

use App\Enums\RevolvingFund\Status;
use App\Enums\RevolvingFund\StatusRemarks;
use Illuminate\Support\Facades\Auth;
use Facades\App\Services\RevolvingFund\ReplenishmentApprovalService;

class RevolvingFundService
{
    public function isVisibleIfPending($record): bool
    {
        return $record->status === Status::PENDING->value && $record->status_remarks === StatusRemarks::FUND_REQUEST_SUBMITTED->value;
    }

    public function canRespond($record): bool
    {
        $userId = Auth::id();

        if (!$userId || (int)$record->added_by !== (int)$userId) {
            return false;
        }

        if (!in_array($record->status, [Status::PENDING->value, Status::IN_PROGRESS->value], true)) {
            return false;
        }

        return $record->discussions()->where('type', 'return')->exists();
    }

    public function canDepartmentHeadReviewReplenishment($record): bool
    {
        return ReplenishmentApprovalService::canCurrentDepartmentHeadReview($record)
            && ReplenishmentApprovalService::hasActionableReplenishment($record);
    }

    public function renderReplenishmentHistoryTable($record): string
    {
        $rows = $record->replenishments()
            ->latest('id')
            ->get()
            ->map(function ($replenishment): string {
                $status = (string)($replenishment->status ?? '-');
                $badgeClasses = match ($status) {
                    Status::PENDING->value => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-300 dark:ring-warning-400/30',
                    Status::RETURNED->value => 'bg-info-50 text-info-700 ring-info-600/20 dark:bg-info-400/10 dark:text-info-300 dark:ring-info-400/30',
                    Status::APPROVED->value => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-300 dark:ring-success-400/30',
                    Status::REJECTED->value => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-300 dark:ring-danger-400/30',
                    Status::REPLENISHED->value => 'bg-primary-50 text-primary-700 ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-300 dark:ring-primary-400/30',
                    default => 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-400/10 dark:text-gray-300 dark:ring-gray-400/30',
                };

                return '<tr class="border-b border-gray-200 dark:border-white/10">'
                    . '<td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">' . e(optional($replenishment->created_at)->format('F d, Y h:i A') ?? '-') . '</td>'
                    . '<td class="px-4 py-3 text-sm">'
                    . '<span class="inline-flex rounded-md px-2 py-1 text-xs font-medium capitalize ring-1 ring-inset ' . $badgeClasses . '">' . e($status) . '</span>'
                    . '</td>'
                    . '<td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">' . e($replenishment->status_remarks ?: '-') . '</td>'
                    . '<td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-200">PHP ' . e(number_format((float)$replenishment->total_amount, 2)) . '</td>'
                    . '<td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-200">PHP ' . e(number_format((float)$replenishment->remaining_amount, 2)) . '</td>'
                    . '<td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-200">PHP ' . e(number_format((float)$replenishment->amount_to_reimburse, 2)) . '</td>'
                    . '<td class="px-4 py-3 text-right text-sm">'
                    . '<a href="' . e(route('filament.admin.resources.replenishments.view', ['record' => $replenishment])) . '" class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400">View</a>'
                    . '</td>'
                    . '</tr>';
            })
            ->implode('');

        return '<div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5">'
            . '<div class="overflow-x-auto">'
            . '<table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">'
            . '<thead class="bg-gray-50 dark:bg-white/5">'
            . '<tr>'
            . '<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Date Submitted</th>'
            . '<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>'
            . '<th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status Remarks</th>'
            . '<th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Amount</th>'
            . '<th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Remaining Amount</th>'
            . '<th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Amount to Reimburse</th>'
            . '<th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Action</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody class="divide-y divide-gray-200 dark:divide-white/10">'
            . $rows
            . '</tbody>'
            . '</table>'
            . '</div>'
            . '</div>';
    }

    public function getFormattedName($record): string
    {
        return "{$record->user->name} ({$record->user->position} | {$record->user->department->department_name})";
    }
}
