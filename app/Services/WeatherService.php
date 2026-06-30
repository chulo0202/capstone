<?php

namespace App\Services;

use App\Models\WeatherPrediction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    public function fetchAndStore(): ?WeatherPrediction
    {
        $apiKey = config('openweather.api_key');
        $location = config('openweather.location');

        if (! $apiKey) {
            return $this->createDemoPrediction($location);
        }

        try {
            $response = Http::get('https://api.openweathermap.org/data/2.5/weather', [
                'q' => $location,
                'appid' => $apiKey,
                'units' => config('openweather.units', 'metric'),
            ]);

            if (! $response->successful()) {
                Log::warning('OpenWeather API failed', ['body' => $response->body()]);

                return $this->createDemoPrediction($location);
            }

            $data = $response->json();
            $weatherMain = strtolower($data['weather'][0]['main'] ?? '');
            $description = $data['weather'][0]['description'] ?? '';
            $windSpeed = $data['wind']['speed'] ?? 0;

            $rainAlert = in_array($weatherMain, ['rain', 'drizzle', 'thunderstorm'], true);
            $stormAlert = $weatherMain === 'thunderstorm' || $windSpeed > 10;

            return WeatherPrediction::create([
                'location' => $data['name'] ?? $location,
                'temperature' => $data['main']['temp'] ?? null,
                'humidity' => $data['main']['humidity'] ?? null,
                'description' => $description,
                'weather_main' => $data['weather'][0]['main'] ?? null,
                'wind_speed' => $windSpeed,
                'rain_alert' => $rainAlert,
                'storm_alert' => $stormAlert,
                'advisory' => $this->generateAdvisory($rainAlert, $stormAlert, $description),
                'fetched_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Weather fetch failed: '.$e->getMessage());

            return $this->createDemoPrediction($location);
        }
    }

    public function getLatest(): ?WeatherPrediction
    {
        return WeatherPrediction::latest('fetched_at')->first();
    }

    protected function generateAdvisory(bool $rainAlert, bool $stormAlert, string $description): string
    {
        if ($stormAlert) {
            return 'Storm alert: Secure crops, livestock, and farm equipment. Avoid field work until conditions improve.';
        }

        if ($rainAlert) {
            return 'Rain expected: Delay fertilizer application. Ensure proper drainage in rice paddies and vegetable plots.';
        }

        return 'Favorable conditions: Good time for land preparation, planting, and scheduled farm activities.';
    }

    protected function createDemoPrediction(string $location): WeatherPrediction
    {
        return WeatherPrediction::create([
            'location' => $location,
            'temperature' => 28.5,
            'humidity' => 75,
            'description' => 'partly cloudy (demo data - configure OPENWEATHER_API_KEY)',
            'weather_main' => 'Clouds',
            'wind_speed' => 3.5,
            'rain_alert' => false,
            'storm_alert' => false,
            'advisory' => 'Configure OPENWEATHER_API_KEY in .env for live weather data.',
            'fetched_at' => now(),
        ]);
    }
}
