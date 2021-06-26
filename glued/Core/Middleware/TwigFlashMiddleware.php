<?php

namespace Glued\Core\Middleware;

use Glued\Core\Middleware\AbstractMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Views\Twig;
use Psr\Container\ContainerInterface as Container;

class TwigFlashMiddleware extends AbstractMiddleware
{

    public function __invoke(Request $request, Handler $handler)
    {
        $this->view->getEnvironment()->addGlobal('flash', $this->flash);
        return $handler->handle($request);
    }
}

