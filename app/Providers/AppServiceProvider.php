<?php

namespace App\Providers;

use Aws\Sns\SnsClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SnsClient::class, fn () => new SnsClient([
            'version' => '2010-03-31',
            'region' => config('services.distribution_sms.region'),
            'retries' => 0,
            'http' => ['connect_timeout' => 3, 'timeout' => 8],
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useBootstrapFive();
    }
}
