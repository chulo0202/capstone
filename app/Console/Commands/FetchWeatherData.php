<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WeatherService;
use App\Services\AtRiskFarmerService;
use App\Services\SmsService;

class FetchWeatherData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch the latest weather data from OpenWeatherMap and send SMS alerts to at-risk farmers';

    /**
     * Execute the console command.
     */
    public function handle(
        WeatherService $weatherService,
        AtRiskFarmerService $atRiskFarmerService,
        SmsService $smsService
    ) {
        $weather = $weatherService->fetchAndStore();

        if (! $weather) {

            $this->error('❌ Failed to fetch weather data.');

            return self::FAILURE;
        }

        $this->info('✅ Weather data fetched successfully.');
        $this->line('Location : '.$weather->location);
        $this->line('Temperature : '.$weather->temperature.'°C');
        $this->line('Alert Level : '.$weather->alert_level);

        /*
        |--------------------------------------------------------------------------
        | Send Weather SMS Alerts
        |--------------------------------------------------------------------------
        */

        if ($weather->alert_level !== 'Normal') {

            $this->info('📨 Sending SMS notifications...');

            $atRiskFarmerService->sendWeatherAlerts($smsService);

            $this->info('✅ SMS notifications sent.');
        } else {

            $this->info('☀ Normal weather detected.');
            $this->info('No SMS notifications were sent.');
        }

        return self::SUCCESS;
    }
}