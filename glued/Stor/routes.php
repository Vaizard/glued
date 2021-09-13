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
    $group->get('/browser', StorController::class . ':storBrowserGui')->setName('app.stor.browser');
    // show stor file (or force download)
    $group->get('/f/{id:[0-9]+}[/{filename}]', StorController::class . ':serveFile')->setName('app.stor.file');
    // update editace stor file (nazev) TODO nemel by tu byt put, kdyz je to update?
    $group->post('/uploader/update', StorController::class . ':uploaderUpdate')->setName('app.stor.uploader.update');
    // copy nebo move z modalu pro copy move
    $group->post('/cpmv', StorController::class . ':itemCopyMove')->setName('app.stor.copymove');
    
 });

$app->group('/api/stor/v1', function (RouteCollectorProxy $group) {
    // upload pres ajax api, taky z post formulare ale bez reloadu stranky, jen vraci nejaky json
    $group->post('/upload', StorControllerApiV1::class . ':uploaderApiSave')->setName('api.stor.uploader.v1');
    // ajax co vraci optiony v jsonu pro select 2 filtr
    $group->get('/filteroptions', StorControllerApiV1::class . ':showFilterOptions')->setName('api.stor.filter.options.v1');
    // ajax, ktery po odeslani filtru vraci soubory odpovidajici vyberu
    $group->get('/filter', StorControllerApiV1::class . ':showFilteredFiles')->setName('api.stor.filter.v1');
    // smazani souboru ajaxem
    $group->post('/delete', StorControllerApiV1::class . ':ajaxDelete')->setName('api.stor.delete.v1');
    // editace nazvu souboru ajaxem
    $group->post('/update', StorControllerApiV1::class . ':ajaxUpdate')->setName('api.stor.update.v1');
    // ajax co vypise vhodne idecka k vybranemu diru, pro copy move
    $group->get('/modalobjects', StorControllerApiV1::class . ':showModalObjects')->setName('api.stor.actionable.v1');
});




