<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhilippineHolidayApiTest extends TestCase
{
    public function test_it_returns_holiday_when_google_calendar_has_event_for_date(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/*/events*' => Http::response([
                'items' => [
                    [
                        'summary' => 'Araw ng Kagitingan',
                        'status' => 'confirmed',
                        'start' => ['date' => '2026-04-09'],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/holidays/philippines/check?date=2026-04-09');

        $response->assertOk()->assertJson([
            'date' => '2026-04-09',
            'is_holiday' => true,
            'holiday_name' => 'Araw ng Kagitingan',
        ]);
    }

    public function test_it_returns_not_holiday_when_google_calendar_has_no_event_for_date(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/*/events*' => Http::response([
                'items' => [],
            ], 200),
        ]);

        $response = $this->getJson('/api/holidays/philippines/check?date=2026-04-08');

        $response->assertOk()->assertJson([
            'date' => '2026-04-08',
            'is_holiday' => false,
            'holiday_name' => null,
        ]);
    }

    public function test_it_ignores_events_that_do_not_start_on_target_date(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/*/events*' => Http::response([
                'items' => [
                    [
                        'summary' => 'Easter Sunday',
                        'status' => 'confirmed',
                        'start' => ['date' => '2026-04-05'],
                        'end' => ['date' => '2026-04-06'],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/holidays/philippines/check?date=2026-04-06');

        $response->assertOk()->assertJson([
            'date' => '2026-04-06',
            'is_holiday' => false,
            'holiday_name' => null,
        ]);
    }
}
