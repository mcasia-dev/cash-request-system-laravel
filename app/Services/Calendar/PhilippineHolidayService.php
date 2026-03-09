<?php
namespace App\Services\Calendar;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PhilippineHolidayService
{
    public function checkDate(string $date): array
    {
        $calendarId = (string) config('services.google_calendar.holiday_calendar_id');
        $baseUrl    = rtrim((string) config('services.google_calendar.base_url'), '/');
        $apiKey     = (string) config('services.google_calendar.api_key');

        if ($calendarId === '' || $baseUrl === '') {
            throw new RuntimeException('Google Calendar holiday configuration is missing.');
        }

        $targetDate = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Manila');

        $query = [
            'singleEvents' => 'true',
            'orderBy'      => 'startTime',
            'timeMin'      => $targetDate->copy()->startOfDay()->utc()->toRfc3339String(),
            'timeMax'      => $targetDate->copy()->endOfDay()->utc()->toRfc3339String(),
            'maxResults'   => 10,
        ];

        if ($apiKey !== '') {
            $query['key'] = $apiKey;
        }

        $response = Http::acceptJson()
            ->timeout(10)
            ->get("{$baseUrl}/calendars/" . rawurlencode($calendarId) . '/events', $query);

        if ($response->failed()) {
            throw new RuntimeException('Google Calendar request failed: ' . $response->status());
        }

        $items = collect($response->json('items', []))
            ->filter(fn(array $item) => ($item['status'] ?? null) !== 'cancelled')
            ->filter(fn(array $item) => $this->startsOnTargetDate($item, $targetDate))
            ->values();

        $firstHoliday = $items->first();

        return [
            'date'         => $targetDate->format('Y-m-d'),
            'is_holiday'   => $firstHoliday !== null,
            'holiday_name' => $firstHoliday['summary'] ?? null,
        ];
    }

    private function startsOnTargetDate(array $event, Carbon $targetDate): bool
    {
        $eventStartDate = $event['start']['date'] ?? null;
        if (is_string($eventStartDate) && $eventStartDate !== '') {
            return $eventStartDate === $targetDate->toDateString();
        }

        $eventStartDateTime = $event['start']['dateTime'] ?? null;
        if (! is_string($eventStartDateTime) || $eventStartDateTime === '') {
            return false;
        }

        return Carbon::parse($eventStartDateTime)
            ->setTimezone('Asia/Manila')
            ->toDateString() === $targetDate->toDateString();
    }
}
