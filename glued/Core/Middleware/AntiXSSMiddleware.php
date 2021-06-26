<?php

namespace Glued\Core\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;


/**
 * Sanitizes user data (request body) using voku/anti-xss, which we use here
 * over `htmlentities($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');` to let innocent
 * html markup in, while killing all <script> tags, data, etc. See
 * https://github.com/voku/anti-xss/blob/master/tests/JsXssTest.php
 * to get a general idea of the library's behavior.
 */

class AntiXSSMiddleware extends AbstractMiddleware
{

    public function __invoke(Request $request, Handler $handler)
    {
        if ($request->getParsedBody()) {
            $sanitized = $this->antixss->xss_clean($request->getParsedBody());
            $request = $request->withParsedBody($sanitized);
        }
        return $handler->handle($request);
    }
}


