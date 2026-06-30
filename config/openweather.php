<?php

return [
    'api_key' => env('OPENWEATHER_API_KEY'),
    'location' => env('OPENWEATHER_LOCATION', 'Manila,PH'),
    'units' => env('OPENWEATHER_UNITS', 'metric'),
];
