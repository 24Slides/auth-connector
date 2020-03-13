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
            'key' => env('SERVICE_AUTH_CRYPT_TOKEN', '3NZ3ICTdumdR1I8sSRQsyzRbsQNyPFql'),
            'url' => env('SERVICE_AUTH_URL'),
            'public' => env('SERVICE_AUTH_PUBLIC'),
            'secret' => env('SERVICE_AUTH_SECRET')
        ],
        'clients' => [
            'mandrill' => [
                'secretKey' => env('SERVICE_MANDRILL_SECRET'),
                'resolver' => \Slides\Connector\Auth\Clients\Mandrill\VariableResolver::class
            ]
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
        'enabled' => env('SERVICE_AUTH_ENABLED', true)
    ]
];