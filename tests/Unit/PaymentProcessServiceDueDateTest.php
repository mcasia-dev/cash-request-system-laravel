<?php

namespace Tests\Unit;

use App\Services\Calendar\PhilippineHolidayService;
use App\Services\CashRequest\PaymentProcessService;
use Carbon\Carbon;
use Tests\TestCase;

class PaymentProcessServiceDueDateTest extends TestCase
{
    public function test_it_moves_weekend_due_date_to_next_monday(): void
    {
        $this->app->instance(PhilippineHolidayService::class, new class extends PhilippineHolidayService
        {
            public function checkDate(string $date): array
            {
                return ['date' => $date, 'is_holiday' => false, 'holiday_name' => null];
            }
        });

        $service = app(PaymentProcessService::class);
        $result = $service->adjustDueDateToWeekday(Carbon::parse('2026-04-11')); // Saturday

        $this->assertSame('2026-04-13', $result);
    }

    public function test_it_moves_holiday_due_date_to_next_non_holiday(): void
    {
        $this->app->instance(PhilippineHolidayService::class, new class extends PhilippineHolidayService
        {
            public function checkDate(string $date): array
            {
                return [
                    'date' => $date,
                    'is_holiday' => $date === '2026-04-09',
                    'holiday_name' => $date === '2026-04-09' ? 'Araw ng Kagitingan' : null,
                ];
            }
        });

        $service = app(PaymentProcessService::class);
        $result = $service->adjustDueDateToWeekday(Carbon::parse('2026-04-09'));

        $this->assertSame('2026-04-10', $result);
    }
}
