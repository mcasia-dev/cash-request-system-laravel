<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
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
                'danger' => Color::Zinc,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\UserInfoClockWidget::class,
                \TomatoPHP\FilamentNotes\Filament\Widgets\NotesWidget::class
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
            ->plugin(FilamentSpatieRolesPermissionsPlugin::make())
            ->plugin(\TomatoPHP\FilamentNotes\FilamentNotesPlugin::make()->useChecklist())
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->databaseNotifications();
    }
}
