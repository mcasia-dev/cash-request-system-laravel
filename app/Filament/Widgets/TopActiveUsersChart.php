<?php

namespace App\Filament\Widgets;

use App\Services\Reports\TopActiveUsersReportService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class TopActiveUsersChart extends Widget
{
    protected static string $view = 'filament.widgets.top-active-users-chart';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 6;

    public static function canView(): bool
    {
        $user = Auth::user();

        return (bool) $user?->isSuperAdmin();
    }

    protected function getViewData(): array
    {
        return app(TopActiveUsersReportService::class)->getReportData();
    }
}
