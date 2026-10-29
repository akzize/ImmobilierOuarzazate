<?php

namespace App\Providers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\Entry;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
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
        $this->translateGeneratedLabels();
        $this->registerFilamentAssets();
    }

    /**
     * Labels Filament generates from field and column names (e.g. "Phone" for `phone`) go through the translator,
     * so the Arabic panel needs no ->label() on every component. Translations live in lang/ar.json.
     */
    private function translateGeneratedLabels(): void
    {
        Field::configureUsing(fn (Field $field) => $field->translateLabel());
        Entry::configureUsing(fn (Entry $entry) => $entry->translateLabel());
        Column::configureUsing(fn (Column $column) => $column->translateLabel());
        BaseFilter::configureUsing(fn (BaseFilter $filter) => $filter->translateLabel());
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
