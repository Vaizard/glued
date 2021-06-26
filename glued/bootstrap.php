<?php
declare(strict_types=1);

use DI\Container;
use Nyholm\Psr7\Factory\Psr17Factory;
use Slim\Factory\AppFactory;
use Slim\Http\Factory\DecoratedResponseFactory;

define("__ROOT__", realpath(__DIR__ . '/..'));
require_once(__ROOT__ . '/vendor/autoload.php');


// Slim 4 comes decoupled from a container solution.
// We must set up the container and pass it to our app.
$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();


// DecoratedResponseFactory provides response decorators such as $response->withJson(). It takes 2 parameters:
// @param \Psr\Http\Message\ResponseFactoryInterface which should be a ResponseFactory originating from the PSR-7 Implementation of your choice
// @param \Psr\Http\Message\StreamFactoryInterface which should be a StreamFactory originating from the PSR-7 Implementation of your choice
// NOTE: Nyholm/Psr17 has one factory which implements Both ResponseFactoryInterface and StreamFactoryInterface see https://github.com/Nyholm/psr7/blob/master/src/Factory/Psr17Factory.php
$nyholmFactory = new Psr17Factory();
$decoratedResponseFactory = new DecoratedResponseFactory($nyholmFactory, $nyholmFactory);

require_once (__ROOT__ . '/glued/container.php');
require_once (__ROOT__ . '/glued/environment.php');
require_once (__ROOT__ . '/glued/events.php');
require_once (__ROOT__ . '/glued/middleware.php');
require_once (__ROOT__ . '/glued/routes.php');

$app->run();
?>
