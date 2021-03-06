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
            'cryptKey' => env('SERVICE_AUTH_CRYPT_TOKEN', 'W48eSUfsDX8YtzarrpMsWknNuA3FsvnU57FD+mNJsfw='),
            'url' => env('SERVICE_AUTH_URL'),
            'public' => env('SERVICE_AUTH_PUBLIC'),
            'secret' => env('SERVICE_AUTH_SECRET')
        ],
        'clients' => [
            'mandrill' => [
                'secretKey' => env('SERVICE_MANDRILL_SECRET')
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