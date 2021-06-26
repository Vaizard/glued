<?php

namespace Glued\Core\Middleware;

use Glued\Core\Classes\Crypto\Crypto;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;


/**
 * Redirects guests to login page when they try to access pages intedned only 
 * for authenticated users by application design (i.e. change password).
 */
class RedirectGuests extends AbstractMiddleware
{

    public function __invoke(Request $request, Handler $handler)
    {
        $response = $handler->handle($request);
        if (!$this->auth->check()) {
            $crypto = new Crypto;
            $en = $crypto->encrypt( $request->getUri()->getPath() , $this->settings['crypto']['reqparams'] );
            $this->flash->addMessage('info', __('Please sign in before continuing'));
            $response = $response->withStatus(302)->withHeader(
                'Location', $this->routerParser->urlFor('core.signin.web') . '?' . http_build_query(['redirect' => $en])
            );
        }
        return $response;
    }


}