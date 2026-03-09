<?php

namespace App\Traits;

use App\Services\Calendar\PhilippineHolidayService;
use Carbon\Carbon;

trait AdjustDueDateToBusinessDayTrait
{
    public static function calculateDueDateFromReleasingDate(Carbon $releasingDate, int $agingDays): string
    {
        $candidateDate = $releasingDate->copy();
        $holidayService = app(PhilippineHolidayService::class);

        if ($agingDays <= 0) {
            return self::adjustDueDateToWeekday($candidateDate);
        }

        $businessDaysCounted = 0;

        for ($i = 0; $i < 366; $i++) {
            $candidateDate->addDay();

            if ($candidateDate->isWeekend() || self::isPhilippineHoliday($candidateDate, $holidayService)) {
                continue;
            }

            $businessDaysCounted++;

            if ($businessDaysCounted >= $agingDays) {
                return $candidateDate->toDateString();
            }
        }

        return self::adjustDueDateToWeekday($candidateDate);
    }

    public static function adjustDueDateToWeekday(Carbon $dueDate): string
    {
        $candidateDate = $dueDate->copy();
        $holidayService = app(PhilippineHolidayService::class);

        // Guard against accidental infinite loops while moving to the next valid date.
        for ($i = 0; $i < 31; $i++) {
            if ($candidateDate->isWeekend()) {
                $candidateDate->addDay();
                continue;
            }

            if (self::isPhilippineHoliday($candidateDate, $holidayService)) {
                $candidateDate->addDay();
                continue;
            }

            return $candidateDate->toDateString();
        }

        return $candidateDate->toDateString();
    }

    private static function isPhilippineHoliday(Carbon $date, PhilippineHolidayService $holidayService): bool
    {
        try {
            $result = $holidayService->checkDate($date->toDateString());
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        return (bool) ($result['is_holiday'] ?? false);
    }
}
