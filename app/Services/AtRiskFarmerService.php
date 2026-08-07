<?php

namespace App\Services;

use App\Models\Farmer;
use App\Models\WeatherPrediction;
use Illuminate\Support\Collection;
use App\Services\SmsService;

class AtRiskFarmerService
{
    public function getAtRiskFarmers(): Collection
    {
        $weather = WeatherPrediction::latest('fetched_at')->first();

        if (!$weather) {
            return collect();
        }

        // Kapag normal ang panahon, walang at-risk farmers
        if ($weather->alert_level === 'Normal') {
            return collect();
        }

        $farmers = $this->filterFarmers($weather->alert_level);

        return $farmers->map(function ($farmer) use ($weather) {

            return [
                'farmer' => $farmer,
                'reason' => $this->getReason($weather->alert_level),
                'recommendation' => $this->getRecommendation(
                    $weather->alert_level,
                    $farmer->crop_type
                ),
            ];

        });
    }

    /**
     * Magpadala ng SMS sa lahat ng At-Risk Farmers.
     */
    public function sendWeatherAlerts(SmsService $smsService): void
    {
        $atRiskFarmers = $this->getAtRiskFarmers();

        foreach ($atRiskFarmers as $risk) {

            $farmer = $risk['farmer'];

            $message =
                "FAMS Weather Alert\n\n".
                "Alert: {$risk['reason']}\n\n".
                "Recommendation:\n".
                "{$risk['recommendation']}\n\n".
                "Stay safe.\n\n".
                "- FAMS";

            $smsService->send(
                $farmer->contact_number,
                $message,
                null,
                $farmer->id
            );
        }
    }

    /**
     * Piliin lamang ang mga farmers na apektado ng kasalukuyang alert.
     */
    private function filterFarmers(string $alert): Collection
    {
        return match ($alert) {

            // Rice at Vegetables ang mas madalas maapektuhan ng malakas na ulan
            'Rain Alert' =>
                Farmer::whereIn('crop_type', [
                    'Rice',
                    'Vegetables'
                ])->get(),

            // Lahat ng farmers
            'Storm Warning' =>
                Farmer::all(),

            // Gulay at mais ang madaling maapektuhan ng init
            'Extreme Heat' =>
                Farmer::whereIn('crop_type', [
                    'Vegetables',
                    'Corn'
                ])->get(),

            // Mais at saging ang mas apektado ng malakas na hangin
            'Strong Wind' =>
                Farmer::whereIn('crop_type', [
                    'Corn',
                    'Banana'
                ])->get(),

            default =>
                collect(),
        };
    }

    private function getReason(string $alert): string
    {
        return match ($alert) {

            'Rain Alert' => 'Heavy Rain',

            'Storm Warning' => 'Storm',

            'Extreme Heat' => 'High Temperature',

            'Strong Wind' => 'Strong Wind',

            default => 'Normal Weather',
        };
    }

    /**
     * Recommendation base sa alert at crop.
     */
    private function getRecommendation(
        string $alert,
        string $crop
    ): string {

        $crop = strtolower($crop);

        return match ($alert) {

            'Rain Alert' => match ($crop) {

                'rice' =>
                    'Delay fertilizer application and monitor irrigation canals.',

                'vegetables' =>
                    'Improve drainage and postpone fertilizer application.',

                default =>
                    'Monitor field conditions carefully.',
            },

            'Storm Warning' =>
                'Secure crops, equipment, and avoid field activities.',

            'Extreme Heat' => match ($crop) {

                'vegetables' =>
                    'Increase irrigation during early morning or late afternoon.',

                'corn' =>
                    'Monitor soil moisture and provide additional watering if needed.',

                default =>
                    'Increase water supply to crops.',
            },

            'Strong Wind' => match ($crop) {

                'corn' =>
                    'Support tall corn plants and avoid pesticide spraying.',

                'banana' =>
                    'Secure banana plants with supports to prevent lodging.',

                default =>
                    'Avoid spraying pesticides during strong winds.',
            },

            default =>
                'Continue normal farming activities.',
        };
    }
}