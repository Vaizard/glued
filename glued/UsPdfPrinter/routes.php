<?php
declare(strict_types=1);

use Glued\Core\Controllers\ProxyController;

$app->get ('/api/pdfprinter/v1', ProxyController::class . ':proxy')->setName('api.pdfprinter.v1')->setArgument('endpoint', $settings['us']['pdfprinter']['server'] . '/api/core/healthcheck/v1/be');

$app->get ('/pdfprinter', ProxyController::class . ':proxy_ui')->setName('app.pdfprinter')->setArgument('endpoint', 'https://zelitomas.gitlab.io/page-generator-frontend/');

