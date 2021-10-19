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

//echo $settings['oidc']['uri']['logout']; die();
// Homepage
$app->get('/', Glued::class)->setName('app.core.home');
$app->group('/core', function (RouteCollectorProxy $route) {
    global $settings;
    $route->get ('/auth/callback', Glued::class . ':signin')->setName('app.core.auth.callback');
    $route->get ('/auth/signout', Glued::class . ':signout')->setName('app.core.auth.signout');
    $route->get ('/auth/confidential/whoami', AuthController::class . ':keycloak_whoami')->setName('app.core.auth.confidential.whoami');
    $route->get ('/auth/adm/users[/{uuid}]', AuthController::class . ':keycloak_adm')->setName('app.core.auth.adm.users');
    $route->get ('/auth/enforce', AuthController::class . ':enforcer')->setName('app.core.auth.enforce');
    $route->get ('/phpinfo', function (Request $request, Response $response) {
        phpinfo();
        return $response;
    })->setName('app.core.phpinfo');
    $route->get ('/phpconst', function(Request $request, Response $response) { 
        highlight_string("<?php\nget_defined_constants() =\n" . var_export(get_defined_constants(true), true) . ";\n?>");
        return $response; 
    })->setName('app.core.phpconst');
});

$app->group('/api/core', function (RouteCollectorProxy $route) {
    $route->get ('/routes/v1', AdmController::class . ':routes')->setName('api.core.routes.v1');
    $route->get ('/ui/routetree/v1', AdmController::class . ':ui')->setName('api.core.ui.routetree.v1');
    $route->get ('/healthcheck/v1/fe', ProxyController::class . ':fe_healthcheck')->setName('api.core.adm.healthcheck.fe.v1')->setArgument('x', 'y');//->setArguments(['becva' => 'lala']);
    $route->get ('/healthcheck/v1/be', ProxyController::class . ':be_healthcheck')->setName('api.core.adm.healthcheck.be.v1');
    $route->get ('/auth/adm/users[/{uuid}]', AuthController::class . ':getusers')->setName('api.core.auth.adm.users.v1');
});

$app->get ('/api/test', ProxyController::class . ':proxy')->setName('api.proxy.test')->setArgument('endpoint', $settings['glued']['protocol'] . $settings['glued']['hostname'] . '/api/core/healthcheck/v1/be');

