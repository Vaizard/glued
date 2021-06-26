<?php
declare(strict_types=1);

return [

    // Slim
    'displayErrorDetails' => true,
    'logErrors' => true,
    'logErrorDetails' => true,
    'debugEngine' => 'Whoops', // Error | Whoops

    // Odan-assets
    'assets' => [
        // Disable JavaScript and CSS compression
        'minify' => 0,
    ],

    'twig' => [
        'default' => true,
    ]

];
