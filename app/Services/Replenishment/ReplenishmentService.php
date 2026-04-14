<?php

namespace App\Services\Replenishment;

use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Auth;

class ReplenishmentService
{
    public static function sumItemAmounts(?array $items): float
    {
        return collect($items ?? [])
            ->sum(function ($item) {
                if (!is_array($item)) {
                    return 0;
                }

                return (float)($item['amount'] ?? 0);
            });
    }

    public static function calculateRemainingAmount(float $initial, float $total): float
    {
        return max($initial - $total, 0);
    }

    /**
     * @return \Closure
     */
    public function getClosure(Set $set, Get $get, ?array $state): void
    {
        $total = ReplenishmentService::sumItemAmounts($state);
        $initial = (float)($get('initial_amount') ?? 0);

        $set('total_amount', number_format($total, 2, '.', ''));
        $set('remaining_amount', number_format(ReplenishmentService::calculateRemainingAmount($initial, $total), 2, '.', ''));
    }


    public function canRespond($record): bool
    {
        $userId = Auth::id();

        if (!$userId || (int)($record->revolvingFund?->user_id ?? 0) !== (int)$userId) {
            return false;
        }

        if (!in_array((string)$record->status, ['pending', 'returned'], true)) {
            return false;
        }

        return $record->discussions()->where('type', 'return')->exists();
    }
}
