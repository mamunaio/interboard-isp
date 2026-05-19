<?php

declare(strict_types=1);

namespace App\Core\Providers;

use App\Core\Tenant\TenantManager;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantManager::class, function () {
            return new TenantManager();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
