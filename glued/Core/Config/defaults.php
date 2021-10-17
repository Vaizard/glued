<?php
declare(strict_types=1);

return [

    /***********************************************************
     * OPTIONS THAT WILL MOST LIKELY CHANGE
     **********************************************************/

    // Routes
    'routes' => [
        'app.core' => [
            'label' => 'Core',
            'icon' => 'fas fa-cog',
        ],
        'api.core' => [
            'label' => 'Core',
            'icon' => 'fas fa-cog',
        ],
        'app.core.auth.callback' => [
            'label' => 'Authenticate',
            'icon' => 'fas fa-sign-in-alt',
        ],
        'app.core.auth.signout' => [
            'label' => 'Signout',
            'icon' => 'fas fa-sign-in-alt',
        ],
        'app.core.auth.confidential.whoami' => [
            'label' => 'Whoami',
            'icon' => 'fas fa-user',
        ],
        'app.core.auth.confidential.adm' => [
            'label' => 'Identity Adm',
            'icon' => 'fas fa-user-cog',
        ],
        'api.core.adm.healthcheck.fe.v1' => [
            'label' => 'Frontend Health',
            'icon' => 'fas fa-heartbeat',
        ],
        'api.core.adm.healthcheck.be.v1' => [
            'label' => 'Backend Health',
            'icon' => 'fas fa-heartbeat',
        ],
        'app.core.home' => [
            'label' => 'Home',
            'icon' => 'fas fa-home',
            'public' => true,
        ],
        'app.core.phpinfo' => [
            'label' => 'Phpinfo()',
            'icon' => 'fab fa-php',
        ],
        'app.core.phpconst' => [
            'label' => 'Phpconst()',
            'icon' => 'fab fa-php',
        ],
        'app.core.auth.enforce' => [
            'label' => 'Authorize',
            'icon' => 'fas fa-key',
        ],
        'api.core.routes.v1' => [
            'label' => 'Routes (v1)',
            'icon' => 'fas fa-sitemap',
        ],
        'api.core.ui.routetree.v1' => [
            'label' => 'Route tree (v1)',
            'icon' => 'fas fa-sitemap',
        ],
    ],


    // Slim
    'displayErrorDetails' => true, // Set to false in production
    'logErrors' => true,
    'logErrorDetails' => true,
    'debugEngine' => 'Whoops', // Error | Whoops
    'determineRouteBeforeAppMiddleware' => true,

    // Glued globals
    'glued' => [
        'timezone' => 'Europe/Prague',
        'hostname' => $_SERVER['SERVER_NAME'] ?? null, // Main domain name (i.e. if $_SERVER is not available)
        'protocol' => 'https://',
    ],

    // Database
    'db' => [
        'host' => $_ENV['MYSQL_HOST'] ?? 'db_host',
        'database' => $_ENV['MYSQL_DATABASE'] ?? 'db_name',
        'username' => $_ENV['MYSQL_USERNAME'] ?? 'db_user',
        'password' => $_ENV['MYSQL_PASSWORD'] ?? 'db_pass',
        'charset' => ' utf8mb4',
        'collation' => ' utf8mb4_unicode_ci'
    ],

    // Geoip
    'geoip' => [
        'geoip_engine' => false,    // Providers: [ false, 'maxmind']. Override to 'maxmind' when maxmind license key is set.
        'maxmind_licence_key' => $_ENV['SECRET_MAXMIND'] ?? '' // Maxmind GeoLite2 Licence key (its free, you just need to sign up for an account).
    ],

    // Monolog
    'logger' => [
        'name' => 'glued',
        'path' =>  __ROOT__ . '/private/log/app.log',
        'level' => \Monolog\Logger::DEBUG,
    ],

    // E-mail (see swiftmailser)
    'smtp' => [
        'smtp' => 'smtp.example.com',
        'port' => 465,
        'encr' => 'ssl',
        'user' => 'you@example.com',
        'pass' => 'very-secret',
        'from' => 'you@example.com',
        'reconnect.after' => '100',  // reconnect after x emails
        'reconnect.delay' => '15',   // wait for x seconds between connections
        'throttle.count' => '50',    // number of emails per minute
        'throttle.data' => '',       // number of bytes per minute
    ],

    // Cryptography keys
    'crypto' => [
        'mail' => $_ENV['SECRET_CRYPTO_MAIL'] ?? 'mail-encryption-key',
        'reqparams' => $_ENV['SECRET_CRYPTO_REQPARAMS'] ?? 'reqparams-encryption-key'
    ],

    // Api keys
    // TODO: get this out of the config
    // see https://www.codementor.io/@ccornutt/keeping-credentials-secure-in-php-kvcbrk55z
    'apis' => [
        'google' => $_ENV['SECRET_GOOGLE'] ?? '',
        'facebook' => '',
        'aliexpress' => '',
        'matrix' => '',
        'mailtrain' => '',
        'twilio' => '',
    ],

    // cURL presets
    'curl' => [
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:81.0) Gecko/20100101 Firefox/91.0',
        CURLOPT_COOKIEJAR => __ROOT__.'/private/cache/cookies.txt',
        CURLOPT_COOKIEFILE => __ROOT__.'/private/cache/cookies.txt',
        CURLOPT_POST => 0,
    ],

    'casbin' => [
        'model' => ($model = 'default'),
        'modelconf' => __ROOT__ . '/glued/Core/Includes/Casbin/'.$model.'.model',
        //'adapter' => 'database',
        'adapter' => 'file',
    ],

    'policies' => [
        'default' => [
            // The `p` permission policy assigns an {action} to a user or role
            // {subject}, in {domain} on a data {object}. Per model definition,
            // `p = subect, domain, object, action`, with wildcards supported
            // on the {object} - for example, '/data/:object/3/*' would evaluate
            // to `/data/[at-least-one-character]/3/[anything-or-nothing]`
            'p' => [ 
                // admininstration role
                [ 'admin', '0', '*', 'c' ],
                [ 'admin', '0', '*', 'r' ],
                [ 'admin', '0', '*', 'u' ],
                [ 'admin', '0', '*', 'd' ],
                // usage role
                [ 'usage', '0', '/ui/worklog', 'r' ],
                [ 'usage', '0', '/ui/core/accounts/self', 'r' ],
                [ 'usage', '0', '/ui/core/profiles/self', 'r' ],
                [ 'usage', '0', '/ui/stor', 'r' ],
            ],
            // The `g` policy assigns a {role} in {domain} to a {user}
            // Per model definition, `g = user, role, domain`.
            'g' => [
                [ '1', 'admin', '0' ],
            ],
            // The `g2` policy creates relations between a domain and its
            // subdomain. Per model definition, `g2 = domain, subdomain`.
            // We will use a special domain 0 as the root domain containg
            // all other subdomains. Hence, every domain will need a 
            // `['0', $domain]` relationship set up upon the creation of
            // the $domain. This can't be preconfigured here.
            'g2' => [ 
                ['0', '1'],
            ],
        ],
    ],

    /***********************************************************
     * OPTIONS TO TWEAK ONLY IF YOU REALLY NEED TO / KNOW HOW TO
     **********************************************************/

    'php' => [
        /** 
         * password_hash() configuration.
         */
        'password_hash_algo' => PASSWORD_ARGON2ID,
        'password_hash_opts' => [ 
            'memory_cost' => 2 * PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
            'time_cost' => 2 * PASSWORD_ARGON2_DEFAULT_TIME_COST,
            'threads' => PASSWORD_ARGON2_DEFAULT_THREADS 
        ],
    ],

    'headers' => [
        /**
         * Feature-policy http header configuration (consumed by the 
         * @see HeadersMiddleware). Changing these defaults may compromise
         * security (i.e. enable unwanted browser apis/features). See 
         * @link https://scotthelme.co.uk/a-new-security-header-feature-policy/
         */ 
        'feature-policy' => [
            'geolocation' => "'self'",
            'midi' => "'self'",
            'notifications' => "'self'",
            'push' => "'self'",
            'sync-xhr' => "'self'",
            'microphone' => "'self'",
            'camera' => "'self'",
            'magnetometer' => "'self'",
            'gyroscope' => "'self'",
            'speaker' => "'self'",
            'vibrate' => "'self'",
            'fullscreen' => "'self'",
            'payment' => "'self'",
        ],

        /**
         * Referrer-policy and content-type-options http header configuration
         * (consumed by the @see HeadersMiddleware). Changing these defaults
         * may compromise security. See 
         * https://scotthelme.co.uk/a-new-security-header-referrer-policy/
         * https://scotthelme.co.uk/hardening-your-http-response-headers/#x-content-type-options
         */ 
        'referrer-policy' => 'strict-origin-when-cross-origin',
        'content-type-options' => 'nosniff',
        // TODO remove unsafe-eval once odan/twig-assets works with csp
        'csp' => [
            'script-src' => [ 
                'self' => true, 
                'allow' => [  
                    'https://' . ( $_SERVER['SERVER_NAME'] ?? 'cli-run-or-unsupported-webserver' ), 
                    'https://id.industra.space' ,
                    'id.industra.space',
                ], 
                'strict-dynamic' => true,
                'unsafe-eval' => true
            ],
            'object-src' => [ 'default-src' => 'false' ],
            'img-src' => [ 'self' => true ],
            'frame-ancestors' => [ 
                'self' => true, 
                'allow' => [ 
                    'https://' . ( $_SERVER['SERVER_NAME'] ?? 'cli-run-or-unsupported-webserver' ),
                    'https://id.industra.space',
                    'id.industra.space',
                ] 
            ],
            'child-ancestors' => [ 
                'self' => false, 
                'allow' => [ 
                    'https://id.industra.space',
                    'id.industra.space',
                ] 
            ],
            'connect-src' => [ 
                'self' => true, 
                'allow' => [ 
                    'https://' . ( $_SERVER['SERVER_NAME'] ?? 'cli-run-or-unsupported-webserver' ), 
                    'https://id.industra.space',
                    'id.industra.space',
                ] 
            ],
            'base-uri' => 'self',
            'require-trusted-types-for' => 'script' // TODO not yet supported https://github.com/paragonie/csp-builder/issues/47
        ],

        /** Optimal production hsts values (see https://hstspreload.org/
         * before setting things up this)
         *   'enable' => false,
         *   'max-age' => 31536000,
         *   'include-sub-domains' => true,
         *   'preload' => true,
         */
        'hsts' => [
            'enable' => true,
            'max-age' => 15552,//552000,
            'include-sub-domains' => false,
            'preload' => false,
        ]
    ],

    /***********************************************************
     * OPTIONS THAT YOU SHOULDN'T HAVE A REASON TO TOUCH UNLESS
     * YOU ARE A GLUED DEVELOPER
     **********************************************************/

    // Twig (set 'cache' to false to disable caching)
    'twig' => [
        'cache' => __ROOT__ . '/private/cache/twig',
        'auto_reload' => true,
        'debug' => false
    ],

    // Twig-translation
    'locale' => [
        'path' => __ROOT__ . '/private/locale',
        'cache' => __ROOT__ . '/private/cache/locale',
        'locale' => 'en_US',
        'domain' => 'messages',
    ],

    // Odan-assets
    'assets' => [
        'path' => __ROOT__ . '/public/assets/cache',
        'url_base_path' => '/assets/cache/',
        // Cache settings
        'cache_enabled' => true,
        'cache_path' => __ROOT__ . '/private/cache',
        'cache_name' => 'assets',
        // Enable JavaScript and CSS compression
        'minify' => 1,
    ]

];
