<?php
declare(strict_types=1);

return [
    'us' => [
        'pdfprinter' => [
             'server' => 'https://' . $_SERVER['SERVER_NAME'],
        ],
    ],

    // Routes
    'routes' => [
        'api.pdfprinter.v1' => [
            'label' => 'PDF Printer (v1)',
            'icon' => 'far fa-file-pdf',
        ],
        'app.pdfprinter' => [
            'label' => 'PDF Printer',
            'icon' => 'far fa-file-pdf',
        ],
        'api.pdfprinter' => [
            'label' => 'PDF Printer',
            'icon' => 'far fa-file-pdf',
        ],
    ],

];
