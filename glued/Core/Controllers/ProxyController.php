<?php

declare(strict_types=1);

namespace Glued\Core\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Http\Message\Authentication\Bearer;
use Glued\Core\Classes\Exceptions\AuthTokenException;

class ProxyController extends AbstractController
{

    public function make_request($endpoint, $params = [], $token = '', $guzzleopts = []) {
        $uri    = $this->urifactory->createUri($endpoint)->withQuery(http_build_query($params));
        $be_req = $this->reqfactory->createRequest('GET', $uri);
        if ($token) {
            $bearer = new Bearer($token);
            $be_req = $bearer->authenticate($be_req);
        }
        $be_res = $this->guzzle->sendAsync($be_req, $guzzleopts)->wait();
        $code = $be_res->getStatusCode();

        if ($code >= 200 && $code < 400) {
            return $be_res;
        } else if ($code == 404) {
            throw new HttpInternalServerErrorException('Auth proxy misconfigured (backend endpoint not found).', $response);
        } else if ($code == 401) {
            throw new HTTPNotUnauthorizedException('Unauthorized.', $response);
        } else if ($code == 403) {
            throw new HTTPNotForbiddenException('Forbidden.', $response);
        } else if ($code >= 500) {
            throw new HTTPBadRequestException('Bad request (backend oopsed).', $response);
        } else {
            throw new HttpInternalServerErrorException('Auth proxy encountered an unexpected backend response.', $response);
        }
    }

    public function proxy(Request $request, Response $response, array $args = []): Response {
        try {
            $endpoint   = $args['endpoint'];
            $params     = $request->getQueryParams();
            $token      = $this->auth->fetch_token($request);
            $guzzleopts = [ 'verify' => false ];
            $be_res     = $this->make_request($endpoint, $params, $token, $guzzleopts);

        } catch (AuthTokenException $e) {
            $data = [ '@status' => 'Unauthenticated.' ];
            return $response->withJson($data)->withCode(401);
        } catch (HttpInternalServerErrorException | HTTPBadRequestException $e) {
            $data = [ '@status' => $e->getMessage() ];
            return $response->withJson($data)->withCode(500);
        } catch (HTTPNotUnauthorizedException $e) {
            $data = [ '@status' => $e->getMessage() ];
            return $response->withJson($data)->withCode(401);
        } catch (HTTPNotForbiddenException $e) {
            $data = [ '@status' => $e->getMessage() ];
            return $response->withJson($data)->withCode(403);
        }
        $data = [
            '@status' => $be_res->getReasonPhrase(),
            '@code' => $be_res->getStatusCode(),
            '@params' => $params,
            '@data' => json_decode((string)$be_res->getBody(), true),
        ];

        return $response->withJson($data);
    }

    public function proxy_ui(Request $request, Response $response, array $args = []): Response {
        try {
            $endpoint   = $args['endpoint'];
            $params     = $request->getQueryParams();
            $token      = $this->auth->fetch_token($request);
            $guzzleopts = [ 'verify' => false ];
            $be_res     = $this->make_request($endpoint, $params, $token, $guzzleopts);
        } catch (AuthTokenException $e) {
            $data = [ '@status' => 'Unauthenticated.' ];
            return $response->withJson($data)->withCode(401);
        } catch (HttpInternalServerErrorException | HTTPBadRequestException $e) {
            $data = [ '@status' => $e->getMessage() ];
            return $response->withJson($data)->withCode(500);
        } catch (HTTPNotUnauthorizedException $e) {
            $data = [ '@status' => $e->getMessage() ];
            return $response->withJson($data)->withCode(401);
        } catch (HTTPNotForbiddenException $e) {
            $data = [ '@status' => $e->getMessage() ];
            return $response->withJson($data)->withCode(403);
        }
        echo $be_res->getBody();
        return $response;
    }



    public function fe_healthcheck(Request $request, Response $response, array $args = []): Response {
        try {
            $endpoint   = 'https://10.146.149.186/api/core/healtcheck/v1/be';
            $params     = $request->getQueryParams();
            $token      = $this->auth->fetch_token($request);
            $guzzleopts = [ 'verify' => false ];
            $be_res     = $this->make_request($endpoint, $params, $token, $guzzleopts);
        } catch (AuthTokenException $e) {
            $data = [ '@status' => 'unauthenticated' ];
            return $response->withJson($data)->withCode(401);
        }
        // TODO catch $this->make_request() exceptions here
     	$data = [
            '@status' => $be_res->getReasonPhrase(),
            '@code' => $be_res->getStatusCode(),
            '@params' => $params,
            '@data' => json_decode((string)$be_res->getBody(), true),
            '@args' => $args,
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
