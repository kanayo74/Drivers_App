<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AwardService;
use App\Services\PaymentService;
use App\Services\FuelDepletionService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * Explicitly bind all services so Laravel's container always finds them.
     */
    public function register(): void
    {
        $this->app->singleton(AwardService::class, function ($app) {
            return new AwardService();
        });

        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService();
        });

        $this->app->singleton(FuelDepletionService::class, function ($app) {
            return new FuelDepletionService();
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
