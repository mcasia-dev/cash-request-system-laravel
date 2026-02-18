<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class UserInfoClockWidget extends Widget
{
    protected static string $view = 'filament.widgets.user-info-clock-widget';

    protected int | string | array $columnSpan = 'full';
}

