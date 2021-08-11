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

class ProxyController extends AbstractJsonController
{

    function __construct() {
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->uriFactory = Psr17FactoryDiscovery::findUriFactory();
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        $this->httpClient = new Client(['verify' => false]);
        // Guzzle Client is hardcoaded here instead of
        // $this->httpClient = Psr18ClientDiscovery::find();
        // Discovery fails.
    }


    private function makeRequest(RequestInterface $request) {
        // So the access token needs to be fetched first somewhere.
        //$accessToken = $this->oidc_svc->callback()->getAccessToken();
        $accessToken = 'toktok';
        $bearer = new Bearer($accessToken);

        $request = $bearer->authenticate($request);
        $response = $this->httpClient->sendRequest($request);

        $code = $response->getStatusCode();
        if ($code >= 200 && $code < 300) {
            return json_decode((string)$response->getBody(), true);
        } else if ($code >= 300 && $code < 400) {
            throw new HTTPRedirectException('Redirect response', $response);
        } else if ($code == 404) {
            throw new HTTPNotFoundException('Endpoint not found', $response);
//        } else if ($code == 401) {
//            throw new HTTPNotFoundException('Unauthorized', $response);
        } else if ($code >= 400) {
            throw new HTTPBadRequestException('Received Bad request', $response);
        } else {
            throw new HTTPBaseException('Received unexpected HTTP response code', $response);
        }
    }


    public function get($endpoint, $params = []) {
        $query = http_build_query($params);
        $uri = $this->uriFactory->createUri($endpoint);
        $uri = $uri->withQuery($query);
        $request = $this->requestFactory->createRequest('GET', $uri);
        return $this->makeRequest($request);
    }


    public function fe_healthcheck(Request $request, Response $response, array $args = []): Response {
        $params = $request->getQueryParams();
        $be_data = $this->get('https://10.146.149.186/api/core/v1/adm/healtcheck/be', $params);
	$data = [
            'ts' => microtime(),
            'status' => 'ok',
            'endpoint' => 'frontend',
            'params' => $params,
            'backend' => $be_data,
        ];
	return $response->withJson($data);
    }



    public function be_healthcheck(Request $request, Response $response, array $args = []): Response {
        $params = $request->getQueryParams();
	$data = [
            'ts' => microtime(),
            'status' => 'ok',
            'params' => $params,
            'endpoint' => 'backend',
        ];
	return $response->withJson($data);
    }


}
