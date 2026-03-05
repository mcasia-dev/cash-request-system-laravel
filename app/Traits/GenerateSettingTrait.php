<?php

namespace App\Traits;

use Joaopaulolndev\FilamentGeneralSettings\Services\GeneralSettingsService;

trait GenerateSettingTrait
{
    public function getAgingDaysFromSettings(): int
    {
        $settings = app(GeneralSettingsService::class)->get();
        $value    = $settings?->more_configs['aging_field'] ?? null;

        $agingDays = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        return $agingDays !== false ? (int) $agingDays : 3;
    }
}
