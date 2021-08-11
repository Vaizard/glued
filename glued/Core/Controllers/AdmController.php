<?php

declare(strict_types=1);

namespace Glued\Core\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\RequestFactoryInterface;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Message\Authentication\Bearer;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Client;

class AdmController extends AbstractTwigController
{

    public function routes(Request $request, Response $response, array $args = []): Response {
        $routes = $this->routecollector->getRoutes();
        foreach ($routes as $route) {
            $item['pattern'] = $route->getPattern();
            $item['methods'] = $route->getMethods();
            $item['name'] = $route->getName();
            $data[] = $item;
        }
	return $response->withJson($data);
    }


}
