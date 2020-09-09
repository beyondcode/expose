<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Host
    |--------------------------------------------------------------------------
    |
    | The expose server to connect to. By default, expose is using the free
    | sharedwithexpose.com server, offered by Beyond Code. You will need a free
    | Beyond Code account in order to authenticate with the server.
    | Feel free to host your own server and change this value.
    |
    */
    'host' => 'sharedwithexpose.com',

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

    /*
    |--------------------------------------------------------------------------
    | Maximum Logged Requests
    |--------------------------------------------------------------------------
    |
    | The maximum number if requests to keep in memory when inspecting your
    | requests and responses in the local dashboard.
    |
    */
    'max_logged_requests' => 25,

    /*
    |--------------------------------------------------------------------------
    | Maximum Allowed Memory
    |--------------------------------------------------------------------------
    |
    | The maximum memory allocated to the expose process.
    |
    */
    'memory_limit' => '128M',

    /*
    |--------------------------------------------------------------------------
    | Skip Response Logging
    |--------------------------------------------------------------------------
    |
    | Sometimes, some responses don't need to be logged. Some are too big,
    | some can't be read (like compiled assets). This configuration allows you
    | to be as granular as you wish when logging the responses.
    |
    | If you run constantly out of memory, you probably need to set some of these up.
    |
    | Keep in mind, by default, BINARY requests/responses are not logged.
    | You do not need to add video/mp4 for example to this list.
    |
    */
    'skip_body_log' => [
        /**
         * | Skip response logging by HTTP response code. Format: 4*, 5*.
         */
        'status' => [
            // "4*"
        ],
        /**
         * | Skip response logging by HTTP response content type. Ex: "text/css".
         */
        'content_type' => [
            //
        ],
        /**
         * | Skip response logging by file extension. Ex: ".js.map", ".min.js", ".min.css".
         */
        'extension' => [
            '.js.map',
            '.css.map',
        ],
        /**
         * | Skip response logging if response size is greater than configured value.
         * | Valid suffixes are: B, KB, MB, GB.
         * | Ex: 500B, 1KB, 2MB, 3GB.
         */
        'size' => '1MB',
    ],

    'admin' => [

        /*
        |--------------------------------------------------------------------------
        | Database
        |--------------------------------------------------------------------------
        |
        | The SQLite database that your expose server should use. This database
        | will hold all users that are able to authenticate with your server,
        | if you enable authentication token validation.
        |
        */
        'database' => implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? __DIR__,
            '.expose',
            'expose.db',
        ]),

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
        | TCP Port Range
        |--------------------------------------------------------------------------
        |
        | Expose allows you to also share TCP ports, for example when sharing your
        | local SSH server with the public. This setting allows you to define the
        | port range that Expose will use to assign new ports to the users.
        |
        | Note: Do not use port ranges below 1024, as it might require root
        | privileges to assign these ports.
        |
        */
        'tcp_port_range' => [
            'from' => 50000,
            'to' => 60000,
        ],

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
            'username' => 'password',
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

        'subdomain_repository' => \App\Server\SubdomainRepository\DatabaseSubdomainRepository::class,

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

            'custom_subdomain_unauthorized' => 'You are not allowed to specify custom subdomains. Please upgrade to Expose Pro. Assigning a random subdomain instead.',

            'no_free_tcp_port_available' => 'There are no free TCP ports available on this server. Please try again later.',
        ],
    ],
];
