<?php
declare(strict_types=1);

return [

    // Database
    'oidc' => [
        'server' => ( $server = 'https://id.example.com' ),
        'realm'  => ( $realm  = 'gluedrealm' ),
        'client' => [
            'admin' => [
                'id'   => 'admin-cli',
                'user' => 'someuser',
                'pass' => 'somepass'
            ],
            'confidential' => [
                'id'     => 'glued-confidental',
                'secret' => 'some-sercret',
            ],
            'public' => [
                'id'     => 'glued-public',
            ],
        ],
        'uri' => [ 
            'base'      => ( $uri_base  = $server . '/auth' ),
            'realm'     => ( $uri_realm = $uri_base . '/realms/' . $realm ),
            'admin'     => $uri_base . '/admin/realms/' . $realm,
            'auth'      => $uri_realm . '/protocol/openid-connect/auth',
            'token'     => $uri_realm . '/protocol/openid-connect/token',
            'user'      => $uri_realm . '/protocol/openid-connect/userinfo',
            'logout'    => $uri_realm . '/protocol/openid-connect/logout',
            'jwks'      => $uri_realm . '/protocol/openid-connect/certs',
            'discovery' => $uri_realm . '/.well-known/openid-configuration',
            'redirect'  => [ 'https://'.$_SERVER['SERVER_NAME'].'/auth/whoami' ],
        ],
        'cookie' => [
            'name' => 'AccessToken',
            'params' => 'SameSite=Lax; Secure; Path=/;',
        ]
    ],
];

