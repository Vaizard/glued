<?php
use Glued\Contacts\Controllers\ContactsController;
use Glued\Core\Middleware\RedirectGuests;
use Glued\Core\Middleware\RestrictGuests;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

// Define the app routes.
$app->group('/api/contacts/v1', function (RouteCollectorProxy $group) {
    $group->get ('[/{uid:[0-9]+}]', ContactsController::class . ':contacts_get_api')->setName('contacts.items.api01'); 
    $group->post('[/{uid:[0-9]+}]', ContactsController::class . ':contacts_post_api');
})->add(RestrictGuests::class);

$app->group('/api/contacts/search/v1', function (RouteCollectorProxy $group) {
    $group->get ('/cz/names/{name}', ContactsController::class . ':cz_names')->setName('contacts.search.cz.names.api01'); 
    $group->get ('/cz/ids/{id}', ContactsController::class . ':cz_ids')->setName('contacts.search.cz.ids.api01'); 
    $group->get ('/eu/ids/{id}', ContactsController::class . ':eu_ids')->setName('contacts.search.eu.ids.api01'); 
})->add(RestrictGuests::class);

$app->group('/contacts', function (RouteCollectorProxy $group) {
    $group->get ('/items[/{uid:[0-9]+}]', ContactsController::class . ':contacts_get_app')->setName('contacts.collection'); 
})->add(RedirectGuests::class);

