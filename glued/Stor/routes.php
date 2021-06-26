<?php
use Slim\Routing\RouteCollectorProxy;
use Glued\Stor\Controllers\StorController;
use Glued\Stor\Controllers\StorControllerApiV1;
use Glued\Core\Middleware\RedirectIfAuthenticated;
use Glued\Core\Middleware\RedirectIfNotAuthenticated;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Define the app routes.

$app->group('/stor', function (RouteCollectorProxy $group) {
    
    // zakladni stranka s browserem
    $group->get('/browser', StorController::class . ':storBrowserGui')->setName('stor.browser');
    // show stor file (or force download)
    $group->get('/get/{id:[0-9]+}[/{filename}]', StorController::class . ':serveFile')->setName('stor.serve.file');
    // update editace stor file (nazev) TODO nemel by tu byt put, kdyz je to update?
    $group->post('/uploader/update', StorController::class . ':uploaderUpdate')->setName('stor.uploader.update');
    // copy nebo move z modalu pro copy move
    $group->post('/item/copymove', StorController::class . ':itemCopyMove')->setName('stor.item.copy.move');
    
 });

$app->group('/api/stor/v1', function (RouteCollectorProxy $group) {
    // upload pres ajax api, taky z post formulare ale bez reloadu stranky, jen vraci nejaky json
    $group->post('/upload', StorControllerApiV1::class . ':uploaderApiSave')->setName('stor.api.uploader');
    // ajax co vraci optiony v jsonu pro select 2 filtr
    $group->get('/filteroptions', StorControllerApiV1::class . ':showFilterOptions')->setName('stor.api.filter.options');
    // ajax, ktery po odeslani filtru vraci soubory odpovidajici vyberu
    $group->get('/filter', StorControllerApiV1::class . ':showFilteredFiles')->setName('stor.api.filtered.files');
    // smazani souboru ajaxem
    $group->post('/delete', StorControllerApiV1::class . ':ajaxDelete')->setName('stor.ajax.delete');
    // editace nazvu souboru ajaxem
    $group->post('/update', StorControllerApiV1::class . ':ajaxUpdate')->setName('stor.ajax.update');
    // ajax co vypise vhodne idecka k vybranemu diru, pro copy move
    $group->get('/modalobjects', StorControllerApiV1::class . ':showModalObjects')->setName('stor.api.modal.objects');
});




