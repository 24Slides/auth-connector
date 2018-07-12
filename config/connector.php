<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Micro-services
     |--------------------------------------------------------------------------
     |
     | The credentials of micro-services which allows you make requests
     |
     */

    'services' => [
        'auth' => [
            'url' => env('SERVICE_AUTH_URL'),
            'public' => env('SERVICE_AUTH_PUBLIC'),
            'secret' => env('SERVICE_AUTH_SECRET'),
        ]
    ],
];