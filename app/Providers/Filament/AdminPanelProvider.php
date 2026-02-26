<?php

namespace App\Providers\Filament;

use App\Filament\Resources\CashRequestResource\Widgets\SampleChart;
use App\Filament\Widgets\MyApprovalDecisionPieChart;
use App\Filament\Widgets\MyReleaseNaturePercentageChart;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\RequestCountOverviewStats;
use App\Filament\Widgets\ReleaseAmountSummaryStats;
use App\Filament\Widgets\SampleGraphChart;
use App\Filament\Widgets\UnliquidatedCashRequestsTable;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Support\Colors\Color;
use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\Auth\CustomLogin;
use App\Filament\Pages\Auth\EditProfile;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Althinect\FilamentSpatieRolesPermissions\FilamentSpatieRolesPermissionsPlugin;
use App\Http\Middleware\ForceLogoutAfterRegistration;
use Joaopaulolndev\FilamentGeneralSettings\FilamentGeneralSettingsPlugin;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use TomatoPHP\FilamentNotes\Filament\Widgets\NotesWidget;
use TomatoPHP\FilamentNotes\FilamentNotesPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login(CustomLogin::class)
            ->registration(Register::class)
            ->profile(EditProfile::class, isSimple: false)
            ->colors([
                'primary' => Color::Red,
                'secondary' => Color::Gray,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
//                \App\Filament\Widgets\UserInfoClockWidget::class,
                RequestCountOverviewStats::class,
                ReleaseAmountSummaryStats::class,
                MyReleaseNaturePercentageChart::class,
                MyApprovalDecisionPieChart::class,
                UnliquidatedCashRequestsTable::class,
                NotesWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                ForceLogoutAfterRegistration::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentSpatieRolesPermissionsPlugin::make(),
                FilamentNotesPlugin::make()
                    ->useChecklist(),
                FilamentApexChartsPlugin::make(),
                FilamentGeneralSettingsPlugin::make()
                    ->canAccess(fn() => auth()->user()->isSuperAdmin())
                    ->setIcon('heroicon-o-cog')
                    ->setNavigationGroup('Administrator')

            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn(): string => view('filament.hooks.database-notification-sound')->render(),
            )
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->databaseNotifications();
    }
}
