<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Credentials of Micro-services
     |--------------------------------------------------------------------------
     |
     | The credentials of micro-services which allows you make requests
     |
     */

    'credentials' => [
        'auth' => [
            'url' => env('SERVICE_AUTH_URL'),
            'public' => env('SERVICE_AUTH_PUBLIC'),
            'secret' => env('SERVICE_AUTH_SECRET'),
        ]
    ],

    /*
     |--------------------------------------------------------------------------
     | Authentication Service
     |--------------------------------------------------------------------------
     |
     | Here you can configure authentication micro-service
     |
     */

    'auth' => [
        'enabled' => true
    ]
];