<?php

namespace App\Filament\Pages;

class Dashboard extends \Filament\Pages\Dashboard
{

    protected static ?string $navigationIcon = "heroicon-o-chart-bar-square";

    public function getColumns(): int|string|array
    {
        return [
            'md' => 2,
            'xl' => 2,
        ];
    }
}
