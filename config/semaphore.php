<?php

return [

    'api_key' => env('SEMAPHORE_API_KEY'),

    'sender_name' => env('SEMAPHORE_SENDER_NAME', 'FAMS'),

    'base_url' => env('SEMAPHORE_BASE_URL', 'https://api.semaphore.co/api/v4'),

];