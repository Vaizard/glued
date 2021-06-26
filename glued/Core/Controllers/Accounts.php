<?php

declare(strict_types=1);

namespace Glued\Core\Controllers;

//use Glued\Core\Classes\Auth\Auth;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException as BadRequest;
use Slim\Exception\HttpNotFoundException as NotFound;
use Throwable;

class Accounts extends AbstractTwigController
{
  
    public function read(Request $request, Response $response, array $args = []): Response {
        try {
            $users = $this->auth->user_read($args['uid']);
        } catch (Throwable $e) {
            if ($e->getCode() == 450) { throw new NotFound($request, 'User not found.'); }
            if ($e->getCode() == 550) { throw new BadRequest($request, 'Wrong user id.'); }
            else { throw new BadRequest($request, 'Something went wrong, sorry.'); }
        }
        
        // TODO DO RBAC HERE
        return $this->render($response, 'Core/Views/accounts.read.twig', [
            'pageTitle' => 'Accounts',
            'users' => $users
        ]);
    }


    public function list(Request $request, Response $response, array $args = []): Response {
        // DO RBAC HERE
        $users = $this->auth->user_list();
        return $this->render($response, 'Core/Views/accounts.list.twig', [
            'pageTitle' => 'Accounts',
            'users' => $users
        ]);
    }

}


