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

use Jose\Component\Core\JWKSet;
use Jose\Easy\Load;
use Glued\Core\Classes\Exceptions\AuthTokenException;
use Glued\Core\Classes\Exceptions\AuthJwtException;
use Glued\Core\Classes\Exceptions\AuthOidcException;
use Glued\Core\Classes\Exceptions\DbException;
use Glued\Core\Classes\Exceptions\TransformException;

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


    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

        try {
            // Get oidc config, jwk signing keys and the access token
            $oidc = $this->settings['oidc'];
            $certs = $this->auth->get_jwks($oidc);
            $accesstoken = $this->auth->fetch_token($request);
          
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
            } catch (\Exception $e) { throw new AuthJwtException($e->getMessage(), $e->getCode(), $e); }

            try {
                // TODO join with events table
                $this->db->where('c_uuid = uuid_to_bin(?, true)', [ $jwt_claims['sub'] ?? '' ]);
                $t_core_users = $this->db->getOne('t_core_users', null);
            } catch (\Exception $e) { throw new DbException($e->getMessage(), $e->getCode(), $e); }

            if ($t_core_users) {
                // TODO join the query above with a table containing scheduled events,
                // periodically update profile data according to identity server.
            } else {

                $account['locale'] = $this->utils->default_locale($jwt_claims['locale'] ?? 'en') ?? 'en_US';

            try {
                $profile = $this->transform
                    ->map('name.0.fn',          'name')
                    ->map('name.0.given',       'given_name')
                    ->map('name.0.family',      'family_name')
                    ->map('name.0.@.src',       'iss')
                    ->map('email.0.uri',        'email')
                    ->map('email.0.@.src',      'iss')
                    ->set('email.0.@.pref',     1)
                    ->set('service.0.kind',     'oidc')
                    ->map('service.0.uri',      'iss')
                    ->map('service.0.handle',   'preferred_username')
                    ->map('website.0.uri',      'website')
                    ->toArray($jwt_claims) ?? [];
            } catch (\Exception $e) { throw new TransformException($e->getMessage(), $e->getCode(), $e); }

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

            // TODO check validify of sub, email, etc.
            // TODO create profile and set attrs
            // TODO handle race conditions and db errors.

        } 
        catch (AuthJwtException | AuthTokenException $e) {
            if ($request->getUri()->getPath() != $this->routecollector->getRouteParser()->urlFor('core.auth.jwtsignin')) {
                $en = $this->crypto->encrypt($request->getUri()->getPath(), $this->settings['crypto']['reqparams']);
                return $handler->handle($request)->withRedirect($this->routecollector->getRouteParser()->urlFor('core.auth.jwtsignin') .'?'. http_build_query(['caller' => $en]));
            }
        }
        catch (DbException $e) { echo $e->getMessage(); die(); }
        catch (TransformException $e) { echo $e->getMessage(); die(); }
        //catch (\Exception $e) { echo 'x'.$e->getMessage(); die(); }



        //if (isset($shadow['sub'])) $request = $request->withAttribute('auth-sub', $shadow['sub']);
        return $handler->handle($request);
    }
}

