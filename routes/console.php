<?php

use App\Enums\CashRequest\Status;
use App\Enums\CashRequest\StatusRemarks;
use App\Models\CashRequest;
use App\Models\ForCashRelease;
use App\Models\ForLiquidation;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Scheduled task to update aging days for cash requests pending liquidation.
 *
 * Runs daily at 00:05 (12:05 AM) to recalculate and update the aging field
 * for all ForLiquidation records whose related cash requests are overdue.
 *
 * Process:
 * - Queries ForLiquidation records with cash requests that have:
 *   - Status: RELEASED
 *   - Status remarks: FOR_LIQUIDATION
 *   - A due date that has passed (before today)
 * - Calculates the number of days overdue (aging) from the due date to today
 * - Updates the aging field only if the calculated value differs from the stored value
 *
 * @return void
 */
Schedule::call(function () {
    $today = Carbon::today();

    ForLiquidation::query()
        ->whereHas('cashRequest', function ($query) use ($today) {
            $query->where('status', Status::RELEASED->value)
                ->where('status_remarks', StatusRemarks::FOR_LIQUIDATION->value)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', $today);
        })
        ->with('cashRequest:id,due_date')
        ->chunkById(200, function ($liquidations) use ($today) {

            foreach ($liquidations as $liquidation) {
                $dueDate = $liquidation->cashRequest?->due_date;

                if (!$dueDate) {
                    continue;
                }

                // $agingDays = Carbon::parse($dueDate)->diffInDays($today);
                $agingDays = $dueDate->diffInDays($today);

                if ((int)$liquidation->aging !== $agingDays) {
                    $liquidation->update(['aging' => $agingDays]);
                }
            }

        });
})
    ->timezone('Asia/Manila')
    ->dailyAt('00:05')
    ->name('update-for-liquidation-aging');

/**
 * Scheduled task to auto-cancel unclaimed cash requests once the release window has passed.
 *
 * Runs every 5 minutes to catch same-day expirations promptly.
 *
 * Criteria:
 * - Cash request status is APPROVED and for releasing
 * - Related ForCashRelease has releasing_date and release window end time
 * - date_released is null (unclaimed)
 * - Current time is after the release window end time
 */
Schedule::call(function () {
    $now = Carbon::now();

    ForCashRelease::query()
        ->whereNull('date_released')
        ->whereNotNull('releasing_date')
        ->whereNotNull('releasing_time_to')
        ->whereHas('cashRequest', function ($query) {
            $query->where('status', Status::APPROVED->value)
                ->where('status_remarks', StatusRemarks::FOR_RELEASING->value);
        })
        ->with('cashRequest:id,status,status_remarks,date_released')
        ->chunkById(200, function ($releases) use ($now) {
            foreach ($releases as $release) {
                $releaseDate = $release->releasing_date;
                $releaseTimeTo = $release->releasing_time_to;

                if (!$releaseDate || !$releaseTimeTo) {
                    continue;
                }

                $releaseWindowEnd = Carbon::parse(
                    $releaseDate->format('Y-m-d') . ' ' . $releaseTimeTo->format('H:i:s')
                );

                if ($now->greaterThan($releaseWindowEnd)) {
                    $release->cashRequest->update([
                        'status' => Status::CANCELLED->value,
                        'status_remarks' => StatusRemarks::UNCLAIMED->value,
                        'reason_for_cancelling' => 'No claim was made within the scheduled release window.',
                    ]);
                }
            }
        });
})
    ->timezone('Asia/Manila')
    ->everyFiveMinutes()
    ->name('cancel-unclaimed-release-requests');
