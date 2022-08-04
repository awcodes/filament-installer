<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Illuminate\Foundation\Vite;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Filament::serving(function () {
            Filament::registerTheme(
                app(Vite::class)('resources/css/filament.css'),
            );
        });
    }
}