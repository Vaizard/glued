<?php
declare(strict_types=1); 
namespace Glued\Core\Middleware;

use Casbin\Enforcer;
use Casbin\Util\Log;
use Glued\Core\Middleware\AbstractMiddleware;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Respect\Validation\Validator as v;
use Slim\Views\Twig;
use Symfony\Contracts\EventDispatcher\Event;
use Twig\TwigFunction;
use Glued\Core\Classes\Utils;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Easy\Load;






/**
 * Deals with RBAC/ABAC
 */
final class AuthorizationMiddleware extends AbstractMiddleware implements MiddlewareInterface {

    /**
     * Gets current jwks signing keys from identity server by
     * - querying the well_known discovery endpoint for the jwks endpoint
     * - querying the jwks endpoint for current keys
     * - filtering current keys for `sig` keys and returning these
     * To improve performance, queries to the identity server endpoints are
     * cached.
     * 
     * @return  array   Array of Jose\Component\Core\JWK objects.
     */
    private function get_jwks(): array {

        $oidc = $this->settings['oidc'];
        $hit = $this->fscache->has('glued_oidc_uri_discovery');
        if ($hit) {
            $conf = (array) json_decode($this->fscache->get('glued_oidc_uri_discovery') ?? []);
            if ($conf['issuer'] != $oidc['uri']['realm']) $hit = false;
        }

        if (!$hit) {
            $json = $this->utils->fetch_uri($oidc['uri']['discovery']);
            $conf = (array) json_decode($json);
            if ($conf['issuer'] != $oidc['uri']['realm']) throw new \Exception('Identity backend configuration mismatch.');
            $this->fscache->set('glued_oidc_uri_discovery', $json, 300); // TODO make the 300s value configurable
        }

        $hit = $this->fscache->has('glued_oidc_uri_jwks');
        if ($hit) {
            $conf = (array) json_decode($this->fscache->get('glued_oidc_uri_jwks') ?? []);
            if (!isset($jwks['keys'])) $hit = false;
        }

        if (!$hit) {
            $json = $this->utils->fetch_uri($oidc['uri']['jwks']);
            $jwks = (array) json_decode($json);
            if (!isset($jwks['keys'])) throw new \Exception('Identity backend certs mismatch.');
            $this->fscache->set('glued_oidc_uri_jwks', $json, 300); // TODO make the 300s value configurable
        }

        $certs = [];
        foreach ($jwks['keys'] as $item) {
            $item = (array) $item;
            if ($item['use'] === 'sig') $certs[] = new JWK($item);
        }
        return $certs;
    }

    private function fetch_token($request) {

        // Check for token in header.
        $header = $request->getHeaderLine($this->settings['oidc']["header"]);
        if (false === empty($header)) {
            if (preg_match($this->settings['oidc']["regexp"], $header, $matches)) {
                //$this->log(LogLevel::DEBUG, "Using token from request header");
                return $matches[1];
            }
        }

        // Token not found in header, try the cookie.
        $cookieParams = $request->getCookieParams();

        if (isset($cookieParams[$this->settings['oidc']['cookie']])) {
            //$this->log(LogLevel::DEBUG, "Using token from cookie");
            if (preg_match($this->settings['oidc']["regexp"], $cookieParams[$this->settings['oidc']['cookie']], $matches)) {
                return $matches[1];
            }
            return $cookieParams[$this->settings['oidc']["cookie"]];
        };

        // If everything fails log and throw.
        //$this->log(LogLevel::WARNING, "Token not found");
        //throw new Exception("Token not found.");
    }


    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

        // Get oidc config, jwk signing keys and the access token
        $oidc = $this->settings['oidc'];
        $certs = $this->get_jwks($oidc);
        $accesstoken = $this->fetch_token($request);

        // Authenticate user. Exceptions (i.e. invalid jwt, database 
        // errors etc.) are handled by the catch below.
        try {
            
            $jwt = Load::jws($accesstoken)   // Load and verify the token in $accesstoken
                ->algs(['RS256', 'RS512'])   // Check if allowed The algorithms are used
                ->exp()                      // Check if "exp" claim is present
                ->iat(1000)                  // Check if "iat" claim is present and within 1000ms leeway
                ->nbf(1000)                  // Check if "nbf" claim is present and within 1000ms leeway
                ->iss($oidc['uri']['realm']) // Check if "nbf" claim is present and matches the realm
                ->keyset(new JWKSet($certs)) // Key used to verify the signature
                ->run();                     // Do it.

            $jwt_claims = $jwt->claims->all() ?? [];
            $jwt_header = $jwt->header->all() ?? [];

            // TODO join with events table
            $this->db->where('c_uuid = uuid_to_bin(?, true)', [ $jwt_claims['sub'] ?? '' ]);
            $t_core_users = $this->db->getOne('t_core_users', null);

            if ($t_core_users) {
                // TODO join the query above with a table containing scheduled events,
                // periodically update profile data according to identity server.
            } else {

                $account['locale'] = $this->utils->default_locale($jwt_claims['locale'] ?? 'en') ?? 'en_US';

                $profile = $this->transform
                    ->map('name.0.fn',          'name')
                    ->map('name.0.given',       'given_name')
                    ->map('name.0.family',      'family_name')
                    ->map('name.0.@.src',       'iss')
                    ->map('email.0.uri',        'email')
                    ->map('email.0.@.src',      'iss')
                    ->map('email.0.@.pref',     '===', $this->transform->rule()->default(1))
                    ->map('service.0.kind',     '===', $this->transform->rule()->default('oidc'))
                    ->map('service.0.uri',      'iss')
                    ->map('service.0.handle',   'preferred_username')
                    ->map('website.0.uri',      'website')
                    ->toArray($jwt_claims) ?? [];

                // log do shadow profile log table
                // TODO shadow profile
                if ($jwt_claims['sub'])  {
                    $data["c_uuid"]     = $this->db->func('uuid_to_bin(?, true)', [$jwt_claims['sub']]);
                    $data["c_profile"]  = json_encode($profile);
                    $data["c_account"]  = json_encode($account);
                    $data["c_email"]  = $jwt_claims['emaild'] ?? 'NULL';
                    $data["c_nick"]  = $jwt_claims['preferred_username'] ?? 'NULL';
                    $this->db->insert('t_core_users', $data);
                } 
            }


            // Pass jwt data to twig
            $this->view->getEnvironment()->addGlobal('jwt_claims', $jwt->claims->all() ?? []);
            $this->view->getEnvironment()->addGlobal('jwt_header', $jwt->header->all() ?? []);        


/*
    "name": "Pavel Stratl",
    "groups": [
        "/art",
        "/art/bily-dum",
        "/stage"
    ],
    "preferred_username": "x",
    "given_name": "Pavel",
    "locale": "en",
    "family_name": "Stratl",
    "email": "pavel@industra.space"
*/

            // TODO check validify of sub, email, etc.
            // TODO create profile and set attrs
            // TODO handle race conditions and db errors.


        } catch (\Exception $e) {
                // Jwt exception
                if ($request->getUri()->getPath() != $this->routecollector->getRouteParser()->urlFor('core.auth.jwtsignin')) {
                    $en = $this->crypto->encrypt($request->getUri()->getPath(), $this->settings['crypto']['reqparams']);
                    return $handler->handle($request)->withRedirect($this->routecollector->getRouteParser()->urlFor('core.auth.jwtsignin') .'?'. http_build_query(['caller' => $en]));
                }
                // TODO Sql exception handling
            }

        //if (isset($shadow['sub'])) $request = $request->withAttribute('auth-sub', $shadow['sub']);
        return $handler->handle($request);
    }
}

