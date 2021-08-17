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
        $oidc_conf_uri = 'https://id.industra.space/auth/realms/t1/.well-known/openid-configuration'; // todo lower case!
        $oidc_conf_iss = 'https://id.industra.space/auth/realms/t1';  // todo lower case!
        $oidc_conf_key = md5($oidc_conf_uri);

        $hit = $this->fscache->has($oidc_conf_key);

        if ($hit) {
            $json = $this->fscache->get($oidc_conf_key);
            $conf = (array) json_decode($json);
            if ($conf['issuer'] != $oidc_conf_iss) $hit = false;
        }

        if (!$hit) {
            $json = $this->utils->fetch_uri($oidc_conf_uri); // we don't catch exeptions here because poor mans data validation below
            $conf = (array) json_decode($json);
            if ($conf['issuer'] != $oidc_conf_iss) throw new \Exception('Identity backend configuration mismatch');
            $this->fscache->set($oidc_conf_key, $json, 300); // 5 minutes
        }

        $oidc_jwks_uri = $conf['jwks_uri']; // todo lower case!
        $oidc_jwks_iss = 'https://id.industra.space/auth/realms/t1';  // todo lower case!
        $oidc_jwks_key = md5($oidc_jwks_uri);

        $hit = $this->fscache->has($oidc_jwks_key);

        if ($hit) {
            $json = $this->fscache->get($oidc_jwks_key);
            $jwks = (array) json_decode($json);
            if (!isset($jwks['keys'])) $hit = false;
        }

        if (!$hit) {
            $json = $this->utils->fetch_uri($oidc_jwks_uri);
            $jwks = (array) json_decode($json);
            if (!isset($jwks['keys'])) throw new \Exception('Identity backend certs mismatch');
            $this->fscache->set($oidc_jwks_key, $json, 300); // 5 minutes
        }

    	$certs = [];
    	foreach ($jwks['keys'] as $item) {
    	    $item = (array) $item;
    	    if ($item['use'] === 'sig') $certs[] = $item;
    	}

        // TODO replace single cert with multiple certs
        $accesstoken = $_COOKIE['AccessToken'] ?? '';
        $jwk = new JWK($certs[0]);
        try {
                $jwt = Load::jws($accesstoken) // We want to load and verify the token in the variable $token
                    ->algs(['RS256', 'RS512']) // The algorithms allowed to be used
                    ->exp() // We check the "exp" claim
                    ->iat(1000) // We check the "iat" claim. Leeway is 1000ms (1s)
                    ->nbf(1000) // We check the "nbf" claim
                    // TODO add proper audience handling
                    //->aud('audience1') // Allowed audience
                    // TODO make configurable
                    ->iss('https://id.industra.space/auth/realms/t1') // Allowed issuer
                    //->sub() // Allowed subject
                    //->jti('0123456789') // Token ID
                    ->key($jwk) // Key used to verify the signature
                    ->run(); // Go!
        } catch (\Exception $e) {
                $en = $this->crypto->encrypt($request->getUri()->getPath(), $this->settings['crypto']['reqparams']);
                return $response->withRedirect($this->routecollector->getRouteParser()->urlFor('core.auth.jwtsignin') .'?'. http_build_query(['caller' => $en]));
        }

        return $this->render($response, 'Core/Views/glued.twig', [
                'certs' => $certs,
                'ahdr' => $accesstoken,
                'jwt_claims' => $jwt->claims->all() ?? [],
                'jwt_header' => $jwt->header->all() ?? [], 
                'pageTitle' => 'Home',
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
