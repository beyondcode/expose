<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Host
    |--------------------------------------------------------------------------
    |
    | The expose server to connect to. By default, expose is using the free
    | expose.dev server, offered by Beyond Code. You will need a free
    | Beyond Code account in order to authenticate with the server.
    | Feel free to host your own server and change this value.
    |
    */
    'host' => 'expose.dev',

    /*
    |--------------------------------------------------------------------------
    | Port
    |--------------------------------------------------------------------------
    |
    | The port that expose will try to connect to. If you want to bypass
    | firewalls and have proper SSL encrypted tunnels, make sure to use
    | port 443 and use a reverse proxy for Expose.
    |
    | The free default server is already running on port 443.
    |
    */
    'port' => 443,

    /*
    |--------------------------------------------------------------------------
    | Auth Token
    |--------------------------------------------------------------------------
    |
    | The global authentication token to use for the expose server that you
    | are connecting to. You can let expose automatically update this value
    | for you by running
    |
    | > expose token YOUR-AUTH-TOKEN
    |
    */
    'auth_token' => '',

    /*
    |--------------------------------------------------------------------------
    | Default TLD
    |--------------------------------------------------------------------------
    |
    | The default TLD to use when sharing your local sites. Expose will try
    | to look up the TLD if you are using Laravel Valet automatically.
    | Otherwise you can specify it here manually.
    |
    */
    'default_tld' => 'test',

    'admin' => [

        /*
        |--------------------------------------------------------------------------
        | Database
        |--------------------------------------------------------------------------
        |
        | The SQLite database that your expose server should use. This datbaase
        | will hold all users that are able to authenticate with your server,
        | if you enable authentication token validation.
        |
        */
        'database' => base_path('database/expose.db'),

        /*
        |--------------------------------------------------------------------------
        | Validate auth tokens
        |--------------------------------------------------------------------------
        |
        | By default, once you start an expose server, anyone is able to connect to
        | it, given that they know the server host. If you want to only allow the
        | connection from users that have valid authentication tokens, set this
        | setting to true. You can also modify this at runtime in the server
        | admin interface.
        |
        */
        'validate_auth_tokens' => false,

        /*
        |--------------------------------------------------------------------------
        | Maximum connection length
        |--------------------------------------------------------------------------
        |
        | If you want to limit the amount of time that a single connection can
        | stay connected to the expose server, you can specify the maximum
        | connection length in minutes here. A maximum length of 0 means that
        | clients can stay connected as long as they want.
        |
        */
        'maximum_connection_length' => 0,

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
        | Subdomain Generator
        |--------------------------------------------------------------------------
        |
        | This is the subdomain generator that will be used, when no specific
        | subdomain was provided. The default implementation simply generates
        | a random string for you. Feel free to change this.
        |
        */
        'subdomain_generator' => \App\Server\SubdomainGenerator\RandomSubdomainGenerator::class,

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
        ],

        /*
        |--------------------------------------------------------------------------
        | User Repository
        |--------------------------------------------------------------------------
        |
        | This is the user repository, which by default loads and saves all authorized
        | users in a SQLite database. You can implement your own user repository
        | if you want to store your users in a different store (Redis, MySQL, etc.)
        |
        */
        'user_repository' => \App\Server\UserRepository\DatabaseUserRepository::class,

        /*
        |--------------------------------------------------------------------------
        | Messages
        |--------------------------------------------------------------------------
        |
        | The default messages that the expose server will send the clients.
        | These settings can also be changed at runtime in the expose admin
        | interface.
        |
        */
        'messages' => [
            'message_of_the_day' => 'Thank you for using expose.',

            'invalid_auth_token' => 'Authentication failed. Please check your authentication token and try again.',

            'subdomain_taken' => 'The chosen subdomain :subdomain is already taken. Please choose a different subdomain.',
        ]
    ]
];
