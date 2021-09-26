<?php
use Glued\Contacts\Controllers\ContactsController;
use Glued\Core\Middleware\RedirectGuests;
use Glued\Core\Middleware\RestrictGuests;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

// Define the app routes.
$app->group('/api/contacts/v1', function (RouteCollectorProxy $group) {
    $group->get ('[/{uid:[0-9]+}]', ContactsController::class . ':contacts_get_api')->setName('api.contacts.v1'); 
    $group->post('[/{uid:[0-9]+}]', ContactsController::class . ':contacts_post_api');
})->add(RestrictGuests::class);

$app->group('/contacts', function (RouteCollectorProxy $group) {
    $group->get ('/items[/{uid:[0-9]+}]', ContactsController::class . ':contacts_get_app')->setName('app.contacts'); 
})->add(RedirectGuests::class);

$app->group('/api/if/regs/v1', function (RouteCollectorProxy $group) {
    $group->get ('/cz/names[/{name}]', ContactsController::class . ':cz_names')->setName('api.if.regs.cz.orgname.v1'); 
    $group->get ('/cz/ids[/{id}]', ContactsController::class . ':cz_ids')->setName('api.if.regs.cz.orgid.v1'); 
    $group->get ('/eu/ids[/{id}]', ContactsController::class . ':eu_ids')->setName('api.if.regs.eu.orgid.v1'); 
})->add(RestrictGuests::class);
