<?php

namespace App\Providers;

use App\Http\Responses\Auth\LogoutResponse;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
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
        // Bound in boot(), after all providers' register() phase, so this wins
        // over Filament's own binding regardless of provider load order — every
        // panel's logout returns to the centralized login rather than its own.
        $this->app->bind(LogoutResponseContract::class, LogoutResponse::class);
    }
}
