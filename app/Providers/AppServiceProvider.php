<?php

namespace App\Providers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureFilamentFields();
        $this->registerFilamentAssets();
    }

    /**
     * Use Filament's JavaScript selects and pickers instead of the browser's native controls, app-wide.
     */
    private function configureFilamentFields(): void
    {
        Select::configureUsing(fn (Select $select) => $select->native(false));
        DatePicker::configureUsing(fn (DatePicker $picker) => $picker->native(false));
        DateTimePicker::configureUsing(fn (DateTimePicker $picker) => $picker->native(false));
        TimePicker::configureUsing(fn (TimePicker $picker) => $picker->native(false));
    }

    /**
     * The map component (Leaflet), built by `npm run build:filament` and published by `php artisan filament:assets`.
     * Both files load only on pages that show a map.
     */
    private function registerFilamentAssets(): void
    {
        FilamentAsset::register([
            AlpineComponent::make('map-picker', resource_path('js/dist/map-picker.js')),
            Css::make('map-picker', resource_path('js/dist/map-picker.css'))->loadedOnRequest(),
        ]);
    }
}
