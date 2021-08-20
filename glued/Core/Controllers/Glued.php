<?php

declare(strict_types=1);

namespace Glued\Core\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Glued\Core\Classes\Users;
use Glued\Core\Classes\Utils;
use Jose\Component\Core\JWK;
use Jose\Easy\Load;

class Glued extends AbstractTwigController
{
    /**
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response
    {

        return $this->render($response, 'Core/Views/glued.twig', [
/*                'certs' => $certs,
                'ahdr' => $accesstoken,
                'jwt_claims' => $jwt->claims->all() ?? [],
                'jwt_header' => $jwt->header->all() ?? [], 
                'pageTitle' => 'Home',*/
        ]);
    }
    public function signin(Request $request, Response $response, array $args = []): Response
    {
        $caller = '';
        if ($enc = $request->getQueryParam('caller', $default = null)) {
                $caller = $this->crypto->decrypt( $enc , $this->settings['crypto']['reqparams'] );
        }

        return $this->render($response, 'Core/Views/auth.twig', [
                'caller' => $caller,
                'hostname' => $this->settings['glued']['hostname'],
        ]);
    }
}
