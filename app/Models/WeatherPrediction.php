<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeatherPrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'location',
        'temperature',
        'humidity',
        'description',
        'weather_main',
        'wind_speed',

        // New Weather Monitoring Fields
        'pressure',
        'rainfall',
        'clouds',
        'weather_icon',
        'alert_level',

        // Existing Fields
        'rain_alert',
        'storm_alert',
        'advisory',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'decimal:2',
            'wind_speed' => 'decimal:2',
            'pressure' => 'decimal:2',
            'rainfall' => 'decimal:2',

            'clouds' => 'integer',

            'rain_alert' => 'boolean',
            'storm_alert' => 'boolean',

            'fetched_at' => 'datetime',
        ];
    }
}