<?php
use Glued\IfGoogleContacts\Controllers\IfGoogleContactsController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

// Define the app routes.
$app->group('/api/if/googlecontacts/v1', function (RouteCollectorProxy $group) {
    $group->get ('[/{uid:[0-9]+}]', IfGoogleContactsController::class . ':api01_get')->setName('if/googlecontacts/api01/get'); 
});
