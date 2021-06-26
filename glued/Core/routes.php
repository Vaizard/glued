<?php
use Geocoder\geocode;
use Glued\Core\Classes\Utils\Utils;
use Glued\Core\Controllers\Accounts;
use Glued\Core\Controllers\AuthController;
use Glued\Core\Controllers\DomainsController as Domains;
use Glued\Core\Controllers\Glued;
use Glued\Core\Controllers\GluedApi;
use Glued\Core\Controllers\Profiles;
use Glued\Core\Controllers\Integrations;
use Glued\Core\Controllers\ProfilesApi;
use Glued\Core\Middleware\RedirectAuthenticated;
use Glued\Core\Middleware\RedirectGuests;
use Glued\Core\Middleware\RestrictGuests;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use Symfony\Component\DomCrawler\Crawler;


// Homepage
$app->get('/', Glued::class)->setName('core.main');

$app->get ('/auth/signin',  AuthController::class . ':keycloak_signin')->setName('core.auth.signin');
$app->get ('/auth/signout', AuthController::class . ':keycloak_signout')->setName('core.auth.signout');
$app->get ('/auth/signup', AuthController::class . ':keycloak_signup')->setName('core.auth.signup');
$app->get ('/auth/whoami',  AuthController::class . ':keycloak_whoami')->setName('core.auth.whoami');


$app->group('', function (RouteCollectorProxy $route) {
    $route->group('', function ($route) {
        $route->get ('/adm/oidc', AuthController::class . ':keycloak_adm');
        $route->get ('/adm/phpinfo', function(Request $request, Response $response) { phpinfo(); return $response; }) -> setName('core.admin.phpinfo.web');
        $route->get ('/adm/phpconst', function(Request $request, Response $response) { highlight_string("<?php\nget_defined_constants() =\n" . var_export(get_defined_constants(true), true) . ";\n?>"); return $response; }) -> setName('core.admin.phpconst.web');
    });
});

$app->group('/api/core/v1', function (RouteCollectorProxy $route) {
    // Everyone or Guests-only
    $route->group('', function (RouteCollectorProxy $route) {
        $route->get ('/auth/whoami', AuthController::class . ':api_status_get')->setName('core.auth.whaomi.api');
        $route->get ('/auth/extend', AuthController::class . ':api_extend_get')->setName('core.auth.extend.api');

    });
});


