<?php

declare(strict_types=1);

namespace Glued\Core\Controllers;

use Glued\Core\Classes\Json\JsonResponseBuilder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GluedApi extends AbstractJsonController
{
    /**
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     *
     * @return Response
     */
    

    public $API_NAME = 'glued';
    public $API_VERSION  = 1;

    public function __invoke(Request $request, Response $response, array $args = []): Response
    {
        $builder = new JsonResponseBuilder($this->API_NAME, $this->API_VERSION);
        $payload = $builder->withCode(200)->build();
        $payload['data']['endpoints']['core/profiles'] = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost() . $this->routerParser->urlFor('core.profiles.list.api01');
        return $response->withJson($payload);
    }
}

