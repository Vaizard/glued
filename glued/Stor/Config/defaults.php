<?php
declare(strict_types=1);

return [

    // Routes
    'routes' => [
        'api.stor' => [
            'label' => "Stor",
            'icon' => 'fas fa-cloud',
        ],
        'app.stor' => [
            'label' => "Stor",
            'icon' => 'fas fa-cloud',
        ],
        'app.stor.browser' => [
            'label' => 'Browser',
            'icon' => 'fas fa-columns',
        ],
        'app.stor.file' => [
            'label' => 'Open file',
            'icon' => 'fas fa-folder-open',
        ],
        'app.stor.uploader.update' => [
            'label' => 'Upload / Update',
            'icon' => 'fas fa-upload',
        ],
        'app.stor.copymove' => [
            'label' => 'Copy / Move',
            'icon' => 'fas fa-copy',
        ],
        'api.stor.uploader.v1' => [
            'label' => 'Upload',
            'icon' => 'fas fa-upload',
        ],
        'api.stor.filter.options.v1' => [
            'label' => 'Filter options',
            'icon' => 'fas fa-sliders-h',
        ],
        'api.stor.filter.v1' => [
            'label' => 'Filter files',
            'icon' => 'fas fa-filter',
        ],
        'api.stor.delete.v1' => [
            'label' => 'Delete',
            'icon' => 'fas fa-trash',
        ],
        'api.stor.update.v1' => [
            'label' => 'Rename',
            'icon' => 'fas fa-edit',
        ],
        'api.stor.actionable.v1' => [
            'label' => 'Get Actionable Files',
            'icon' => 'fas fa-copy',
        ],
    ],

];





/*
unction (RouteCollectorProxy $group) {
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
*/