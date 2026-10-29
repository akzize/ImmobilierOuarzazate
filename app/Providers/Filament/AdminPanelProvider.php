<?php

namespace App\Providers\Filament;

use App\Enums\Language;
use App\Filament\Pages\Auth\EditProfile;
use App\Http\Controllers\SwitchLanguageController;
use App\Http\Middleware\ApplyUserLocale;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->login()
            ->emailVerification()
            ->passwordReset()
            ->profile(EditProfile::class)
            ->brandLogo(fn (): View => view('filament.admin.logo'))
            ->favicon(asset('images/3a9ar-ouarzazate-logo-square.png'))
            ->colors([
                // The public site's primary colour (hsl(184 79% 22%)).
                'primary' => '#0C5E64',
            ])
            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            // Persistent: Livewire requests (table actions, form saves) keep the user's language too.
            ->middleware([
                ApplyUserLocale::class,
            ], isPersistent: true)
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authenticatedRoutes(function (): void {
                Route::get('language/{language}', SwitchLanguageController::class)->name('language');
            })
            ->userMenuItems([
                Action::make('switchLanguage')
                    ->label(fn (): string => self::otherLanguage()->getNativeName())
                    ->icon(Heroicon::OutlinedLanguage)
                    ->url(fn (): string => route('filament.admin.language', ['language' => self::otherLanguage()])),
            ]);

        if (config('app.env') === 'local' || env('FILAMENT_REGISTRATION_ENABLED') === true) {
            $panel->registration();
        }

        return $panel;
    }

    /**
     * The panel speaks English or Arabic; the user menu offers the one not in use.
     */
    private static function otherLanguage(): Language
    {
        return app()->isLocale(Language::Arabic->value) ? Language::English : Language::Arabic;
    }
}
