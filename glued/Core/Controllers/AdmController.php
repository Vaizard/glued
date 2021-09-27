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
use Glued\Core\Classes\Auth;

class AdmController extends AbstractTwigController
{


    public function routes(Request $request, Response $response, array $args = []): Response {
        $data = $this->utils->get_routes();
	    return $response->withJson($data);
    }

    public function ui(Request $request, Response $response, array $args = []): Response {
        $data = $this->utils->get_navigation( $this->utils->get_current_route($request) );

        return $response->withJson($data);
    }


}
