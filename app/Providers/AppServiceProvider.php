<?php

namespace App\Providers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
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
}
