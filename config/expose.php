<?php

return [
    'host' => 'expose.dev',
    'port' => 443,
    'auth_token' => '',

    'admin' => [

        'database' => base_path('database/expose.db'),

        'validate_auth_tokens' => false,

        /*
        |--------------------------------------------------------------------------
        | Subdomain
        |--------------------------------------------------------------------------
        |
        | This is the subdomain that your expose admin dashboard will be available at.
        | The given subdomain will be reserved, so no other tunnel connection can
        | request this subdomain for their own connection.
        |
        */
        'subdomain' => 'expose',

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        |
        | The admin dashboard of expose is protected via HTTP basic authentication
        | Here you may add the user/password combinations that you want to
        | accept as valid logins for the dashboard.
        |
        */
        'users' => [
            'username' => 'password'
        ]
    ]
];
