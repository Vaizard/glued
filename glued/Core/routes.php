<?php
use Geocoder\geocode;
use Glued\Core\Controllers\Glued;
use Glued\Core\Classes\Utils\Utils;
use Glued\Core\Controllers\AuthController;
use Glued\Core\Controllers\AdmController;
use Glued\Core\Controllers\ProxyController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;


// Homepage
$app->get('/', Glued::class)->setName('core.main');

$app->get ('/auth/callback',  Glued::class . ':signin')->setName('core.auth.jwtsignin');
$app->get ('/auth/signout', AuthController::class . ':keycloak_signout')->setName('core.auth.signout');
$app->get ('/auth/whoami',  AuthController::class . ':keycloak_whoami')->setName('core.auth.whoami');
$app->get ('/auth/enforce',  AuthController::class . ':enforcer');


$app->group('', function (RouteCollectorProxy $route) {
    $route->group('', function ($route) {
        $route->get ('/adm/oidc', AuthController::class . ':keycloak_adm');
        $route->get ('/adm/routes', AdmController::class . ':routes');
        $route->get ('/adm/phpinfo', function(Request $request, Response $response) {
            phpinfo();
            return $response;
        }) -> setName('core.admin.phpinfo.web');
        $route->get ('/adm/phpconst', function(Request $request, Response $response) { 
            highlight_string("<?php\nget_defined_constants() =\n" . var_export(get_defined_constants(true), true) . ";\n?>");
            return $response; 
        }) -> setName('core.admin.phpconst.web');
    });
});

$app->group('/api/core/v1', function (RouteCollectorProxy $route) {
    // Everyone or Guests-only
    $route->group('', function (RouteCollectorProxy $route) {
        $route->get ('/auth/whoami', ProxyController::class . ':api_status_get')->setName('core.auth.whaomi.api');
        $route->get ('/adm/healtcheck/fe', ProxyController::class . ':fe_healthcheck')->setName('core.adm.healtcheck.fe.api');
        $route->get ('/adm/healtcheck/be', ProxyController::class . ':be_healthcheck')->setName('core.adm.healtcheck.be.api');
        $route->get ('/adm/routes', AdmController::class . ':routes')->setName('core.adm.routes');

    });
});


