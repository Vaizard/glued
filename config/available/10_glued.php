<?php
declare(strict_types=1);

return [

    // Glued globals
    'glued' => [
        'hostname' => $_SERVER['SERVER_NAME'] ?? null, // Main domain name (i.e. if $_SERVER is not available)
    ],

];
