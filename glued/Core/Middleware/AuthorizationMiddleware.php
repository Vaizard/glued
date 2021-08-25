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
            $conf = (array) json_decode($this->fscache->get('glued_oidc_uri_discovery'));
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
            $conf = (array) json_decode($this->fscache->get('glued_oidc_uri_jwks'));
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


    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get oidc config, jwk signing keys and the access token
        $oidc = $this->settings['oidc'];
        $certs = $this->get_jwks($oidc);
        $accesstoken = $_COOKIE['AccessToken'] ?? '';

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

            $filter = [ 'sub', 'email', 'preferred_username', 'website', 'groups', 'given_name', 'family_name', 'name', 'locale' ];
            $claims = array_intersect_key($jwt->claims->all() ?? [], array_flip($filter)); 

            // TODO join with events table
            $this->db->where('c_uuid = uuid_to_bin(?, true)', [ $claims['sub'] ?? '' ]);
            $t_core_users = $this->db->getOne('t_core_users', null);
            if ($t_core_users) {
                // TODO join the query above with a table containing scheduled events,
                // periodically update profile data according to identity server.
            } else {
                $profile['kind']['natural'] = 1;
                $profile['name'][0]['fn'] = $claims['name'];
                $profile['name'][0]['given'] = $claims['given_name'];
                $profile['name'][0]['family'] = $claims['family_name'];
                $profile['name'][0]['@']['src'] = $oidc['uri']['realm'];
                $profile['email'][0]['uri'] = $claims['email'];
                $profile['email'][0]['@']['src'] = $oidc['uri']['realm'];
                $profile['email'][0]['@']['pref'] = 1;
                $profile['service'][0]['kind']   = 'oidc';
                $profile['service'][0]['uri']    = $oidc['uri']['realm'];
                $profile['service'][0]['handle'] = $claims['preferred_username'];
                $profile['website'][0]['uri']    = $claims['website'];
                $account['locale'] = $claims['locale'];
                // log do shadow profile log table
                // a udelat zapis do profile a accoiunts tables
                // TODO create profile
                // TODO shadow profile
            if ($claims['sub']) $data["c_uuid"] = $this->db->func('uuid_to_bin(?, true)', [$claims['sub']]);
            if ($claims['email']) $data["c_email"] = $claims['email'];
            if ($claims['preferred_username']) $data["c_nick"] = $claims['preferred_username'];
            if ($claims['email']) $data["c_email"] = $claims['email'];

            
            //if ($this->db->insert('t_core_users', $data)) $respond = 'Welcome';
            //else $respond = 'Hello again';
            //$this->view->getEnvironment()->addGlobal('core_users', $respond);        

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

