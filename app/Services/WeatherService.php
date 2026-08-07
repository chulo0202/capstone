<?php

namespace App\Services;

use App\Models\WeatherPrediction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    public function fetchAndStore(): ?WeatherPrediction
    {
        $apiKey = config('services.openweather.api_key');
        $location = config('services.openweather.location');

        if (! $apiKey) {
            return $this->createDemoPrediction($location);
        }

        try {
            $response = Http::get(config('services.openweather.base_url') . '/weather', [
                'q' => $location,
                'appid' => $apiKey,
                'units' => config('services.openweather.units', 'metric'),
                'lang' => config('services.openweather.language', 'en'),
            ]);

            if (! $response->successful()) {
                Log::warning('OpenWeather API failed', [
                    'body' => $response->body()
                ]);

                return $this->createDemoPrediction($location);
            }

            $data = $response->json();

            $temperature = $data['main']['temp'] ?? null;
            $humidity = $data['main']['humidity'] ?? null;
            $pressure = $data['main']['pressure'] ?? null;
            $windSpeed = $data['wind']['speed'] ?? 0;
            $clouds = $data['clouds']['all'] ?? 0;
            $rainfall = $data['rain']['1h'] ?? 0;

            $weatherMain = $data['weather'][0]['main'] ?? '';
            $description = $data['weather'][0]['description'] ?? '';
            $icon = $data['weather'][0]['icon'] ?? '';

            $alertLevel = $this->determineAlertLevel(
                $temperature,
                $windSpeed,
                $rainfall,
                $weatherMain
            );

            return WeatherPrediction::create([
                'location' => $data['name'] ?? $location,

                'temperature' => $temperature,
                'humidity' => $humidity,
                'pressure' => $pressure,
                'rainfall' => $rainfall,
                'clouds' => $clouds,

                'description' => $description,
                'weather_main' => $weatherMain,
                'weather_icon' => $icon,

                'wind_speed' => $windSpeed,

                'rain_alert' => in_array(strtolower($weatherMain), [
                    'rain',
                    'drizzle',
                    'thunderstorm'
                ]),

                'storm_alert' => strtolower($weatherMain) === 'thunderstorm',

                'alert_level' => $alertLevel,

                'advisory' => $this->generateAdvisory($alertLevel),

                'fetched_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return $this->createDemoPrediction($location);
        }
    }

    public function getLatest(): ?WeatherPrediction
    {
        return WeatherPrediction::latest('fetched_at')->first();
    }

    protected function determineAlertLevel(
        $temperature,
        $wind,
        $rainfall,
        $weatherMain
    ): string {

        $weatherMain = strtolower($weatherMain);

        if ($weatherMain === 'thunderstorm') {
            return 'Storm Warning';
        }

        if ($temperature >= 35) {
            return 'Extreme Heat';
        }

        if ($wind >= 10) {
            return 'Strong Wind';
        }

        if ($rainfall >= 5 || $weatherMain === 'rain') {
            return 'Rain Alert';
        }

        return 'Normal';
    }

    protected function generateAdvisory(string $alertLevel): string
    {
        return match ($alertLevel) {

            'Storm Warning' =>
                'Secure crops and farm equipment. Avoid field work until the storm passes.',

            'Rain Alert' =>
                'Delay fertilizer application and ensure proper drainage.',

            'Extreme Heat' =>
                'Increase irrigation and avoid planting during peak heat.',

            'Strong Wind' =>
                'Avoid spraying pesticides and secure lightweight structures.',

            default =>
                'Weather conditions are favorable for normal farming activities.',
        };
    }

    protected function createDemoPrediction(string $location): WeatherPrediction
    {
        return WeatherPrediction::create([

            'location' => $location,

            'temperature' => 29,
            'humidity' => 78,
            'pressure' => 1012,
            'rainfall' => 0,
            'clouds' => 40,

            'description' => 'Partly cloudy (Demo)',
            'weather_main' => 'Clouds',
            'weather_icon' => '02d',

            'wind_speed' => 3,

            'rain_alert' => false,
            'storm_alert' => false,

            'alert_level' => 'Normal',

            'advisory' => 'Configure OPENWEATHER_API_KEY to receive live weather updates.',

            'fetched_at' => now(),
        ]);
    }
}