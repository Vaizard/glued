<?php

namespace Glued\Core\Middleware;

use Glued\Core\Middleware\AbstractMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Views\Twig;
use Psr\Container\ContainerInterface as Container;


/**
 */
class TwigCspMiddleware extends AbstractMiddleware
{
    protected $nonce;
    protected $c;

    public function __construct(array $nonce, Container $c)
    {
        $this->nonce = $nonce;
        $this->c = $c;
    }

    public function __invoke(Request $request, Handler $handler)
    {
        $this->view->getEnvironment()->addGlobal('csp_nonce', $this->nonce);
        return $handler->handle($request);
    }
}

