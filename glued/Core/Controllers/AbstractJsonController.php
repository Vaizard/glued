<?php

declare(strict_types=1);

namespace Glued\Core\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


abstract class AbstractJsonController extends AbstractController
{
    /**
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     *
     * @return Response
     */

    public function json_stub_response(int $code, $message = null, $data = null, $links = null, $pagination = null, $embeds = null) {

        $status[200] = 'success';
        $status[201] = 'created';
        $status[400] = 'bad request';
        $status[401] = 'unauthorized';
        $status[403] = 'forbidden';
        $status[404] = 'not found';
        $status[500] = 'internal server error';
        if (!array_key_exists($code, $status)) { $status[$code] = "unknown"; }

        if ($this->API_NAME == '') { throw new \Exception('Api name not defined'); }
        if ($this->API_VERSION == '') { throw new \Exception('Api version not defined'); }

        $payload = array(
            'api' => $this->API_NAME,        //self::API_NAME,
            'version' => $this->API_VERSION, //self::API_VERSION,
            'code' => $code,
            'status' => $status[$code],
            'response_ts' => time(),
            'response_id' => uniqid()
        );
        
        if (!empty($message)) { $payload['message'] = $message; }
        if (!empty($pagination)) { $payload['pagination'] = $pagination; }
        if (!empty($embeds)) { $payload['embeds'] = $embeds; }
        if (!empty($links)) { $payload['links'] = $links; }
        if ( ($code === 200) or ($code === 201) ) { $payload['data'] = $data; }

        return $payload;
    }




}
