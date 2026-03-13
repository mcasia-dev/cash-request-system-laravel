<?php

namespace App\Services\CashRequest;

use App\Enums\CashRequest\Status;
use App\Models\CashRequest\ForLiquidation;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

class CashRequestService
{
    /**
     * Get the liquidation record for the given cash request, with a simple in-memory cache.
     *
     * @param $record
     * @return ForLiquidation|null
     */
    public function getLiquidationFor($record): ?ForLiquidation
    {
        static $cache = [];

        if (!array_key_exists($record->id, $cache)) {
            $cache[$record->id] = ForLiquidation::where('cash_request_id', $record->id)->first();
        }

        return $cache[$record->id];
    }

    /**
     * Get the most recent activity for the given cash request and event, with a simple cache.
     *
     * @param $record
     * @param string $event
     * @return Activity|null
     */
    public function getLatestActivity($record, string $event): ?Activity
    {
        static $cache = [];
        $key = $record->id . '|' . $event;

        if (!array_key_exists($key, $cache)) {
            $cache[$key] = Activity::query()
                ->where('subject_type', $record::class)
                ->where('subject_id', $record->id)
                ->where('event', $event)
                ->latest('created_at')
                ->with('causer')
                ->first();
        }

        return $cache[$key];
    }

    /**
     * Build the liquidation action closure to save receipts and update status.
     * @return \Closure
     */
    public static function getLiquidateAction(): \Closure
    {
        return function ($record, array $data) {
            try {
                app(LiquidationService::class)->liquidate($record, $data, Auth::user());
            } catch (ValidationException $exception) {
                $message = collect($exception->errors())
                    ->flatten()
                    ->first() ?? 'Liquidation submission failed.';

                Notification::make()
                    ->title((string)$message)
                    ->danger()
                    ->send();

                throw $exception;
            }
        };
    }

    public static function canCancel($record): bool
    {
        return ($record->status === Status::PENDING->value || $record->status === Status::IN_PROGRESS->value) && !$record->is_override && $record->status_remarks != null;
    }

    /**
     * Build the cancel action closure to mark a request as cancelled.
     * @return \Closure
     */
    public static function getCancelAction(): \Closure
    {
        return function ($record, array $data) {
            app(CancellationService::class)->cancel($record, $data, Auth::user());
        };
    }
}
