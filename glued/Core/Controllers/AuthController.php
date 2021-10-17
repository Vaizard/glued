<?php


declare(strict_types=1);

namespace Glued\Core\Controllers;
use Firebase\JWT\JWT;
use Glued\Core\Classes\Auth\Auth;
use Glued\Core\Classes\Crypto\Crypto;
use Glued\Core\Classes\Json\JsonResponseBuilder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;
use Facile\OpenIDClient\Service\Builder\RevocationServiceBuilder;
use Facile\OpenIDClient\Service\Builder\UserInfoServiceBuilder;
use Keycloak\Admin\KeycloakClient;
use \Exception;

class AuthController extends AbstractTwigController

{

    /**
     * List users known to the keycloak id server. Uses
     * https://github.com/MohammadWaleed/keycloak-admin-client
     * 
     * @param  RequestInterface $request 
     * @param  ResponseInterface $response
     * @return [type]           [description]
     */


    public function getusers(Request $request, Response $response, $args): Response {
        $users = $this->oidc_adm->getUsers();
        return $response->withJson($users);
    }

    public function keycloak_adm(Request $request, Response $response, $args): Response {
        $uuid = $args['uuid'] ?? null;
        $routes = $this->utils->get_navigation( $this->utils->get_current_route($request) );

        if ($uuid) {
            $user = $this->oidc_adm->getUser([ 'id' => $uuid ]);
            return $this->render($response, 'Core/Views/pages/user.twig', [ 'user' => $user, 'routes' => $routes ]);
        } else {
            $users = $this->oidc_adm->getUsers();
            return $this->render($response, 'Core/Views/pages/users.twig', [ 'users' => $users, 'routes' => $routes ]);
        }
    }

    public function keycloak_signin($request, $response) {
        $settings = [];
        //$settings['login_hint']  = 'user_username';
        $settings['nonce']         = 'somenonce';
        $settings['response_mode'] = 'query';
        $settings['response_type'] = 'code';
        $settings['scope'] = 'openid';
        //$settings['response_type'] = 'code id_token token';

        // ISSUE: Setting response_mode to query lands me on the 
        // keycloak_whoami() callback below, which works until I 
        // try to get the refresh token. Then things fail with 
        // Invalid token provided / The following claims are mandatory: at_hash.
        // To have that, i need response type 'code id_token token',
        // if I set that, I have to declare the nonce, but then the keycloak_whoami()
        // endpoint fails with Response_mode 'query' not allowed for implicit or 
        // hybrid flow (invalid_request). If I change from 'query' to 'fragment', the
        // tokenset is empty

        $authorizationService = $this->oidc_svc;
        $redirectAuthorizationUri = $authorizationService->getAuthorizationUri(
            $this->oidc_cli, $settings);

        // print("<pre>".print_r($redirectAuthorizationUri,true)."</pre>");
        header('Location: '.$redirectAuthorizationUri);
        exit();
    }

    public function keycloak_whoami($request, $response) {
        $settings = [];
        //$settings['login_hint']  = 'user_username';
        $settings['nonce']         = 'somenonce';
        $settings['response_mode'] = 'query';
        $settings['response_type'] = 'code';
        $settings['scope'] = 'openid';
        //$settings['response_type'] = 'code id_token token';

        $client = $this->oidc_cli;
        $authorizationService = $this->oidc_svc;
        $callbackParams = $authorizationService->getCallbackParams($request, $client, $settings);
        echo "<b>Callback params</b><br>"; print_r($callbackParams); echo "<br>";

        //die('<br>dada');

        // ISSUE Upon reloading, things fail on the callback with Code not valid (invalid grant)
        // AHA1 Each authorization code can be used only once, to generate single new access token.
        // As such, generating multiple access tokens from one code is not possible. 

        $tokenSet = $authorizationService->callback($client, $callbackParams);

        $code =  $tokenSet->getCode();
        $state =  $tokenSet->getState();
        $type =  $tokenSet->getTokenType();
        $idToken = $tokenSet->getIdToken();           // Unencrypted id_token
        $accessToken = $tokenSet->getAccessToken();   // Access token
        $claims = $tokenSet->claims();                // IdToken claims (if id_token is available)
        $refreshToken = $tokenSet->getRefreshToken(); // Refresh token
        $exp = $tokenSet->getExpiresIn();

        echo "<b>Token set</b><br>";       print_r($tokenSet); echo "<br>";
        echo "<b>Claims</b><br>";          print_r($claims); echo "<br>";
        echo "<b>ID token</b><br>";        print_r($idToken); echo "<br>";
        echo "<b>Access token</b><br>";    print_r($accessToken); echo "<br>";
        echo "<b>Refresh token</b><br>";   print_r($refreshToken); echo "<br>";
        echo "<b>Expires in</b><br>";      print_r($exp); echo "<br>";
        echo "<b>Code</b><br>";            print_r($code); echo "<br>";
        echo "<b>Token type</b><br>";      print_r($type); echo "<br>";
        echo "<b>State</b><br>";           print_r($state); echo "<br>";

        // Get user info
        //$userInfoService = (new UserInfoServiceBuilder())->build();
        //$userInfo = $userInfoService->getUserInfo($client, $tokenSet);
        //echo "<b>userInfo</b><br>";   print_r($userInfo); echo "<br>";

        die('<br>lalala');


        // https://github.com/facile-it/php-openid-client/blob/ef61cfb321bf323c9dcacd466ca609297ed28cfa/src/Service/AuthorizationService.php#L214
        // https://github.com/facile-it/php-openid-client/blob/ef61cfb321bf323c9dcacd466ca609297ed28cfa/src/Service/AuthorizationService.php#L244

        // Refresh the token
        // This fails with `Invalid token provided, The following 
        // claims are mandatory: at_hash. (0)`, when using just the code response type.


        $tokenSet = $authorizationService->refresh($client, $refreshToken);
        die('lala');
    }


    public function keycloak_whoami_bak($request, $response) {
        $settings = [];
        //$settings['login_hint']  = 'user_username';
        $settings['nonce']         = 'somenonce';
        $settings['response_mode'] = 'query';
        $settings['response_type'] = 'code';
        $settings['scope'] = 'openid';
        //$settings['response_type'] = 'code id_token token';

        $client = $this->oidc_cli;
        $authorizationService = $this->oidc_svc;
        $callbackParams = $authorizationService->getCallbackParams($request, $client, $settings);
        echo "<b>Callback params</b><br>"; print_r($callbackParams); echo "<br>";

        //die('<br>dada');

        // ISSUE Upon reloading, things fail on the callback with Code not valid (invalid grant)
        // AHA1 Each authorization code can be used only once, to generate single new access token.
        // As such, generating multiple access tokens from one code is not possible. 

        $tokenSet = $authorizationService->callback($client, $callbackParams);

        $code =  $tokenSet->getCode();
        $state =  $tokenSet->getState();
        $type =  $tokenSet->getTokenType();
        $idToken = $tokenSet->getIdToken();           // Unencrypted id_token
        $accessToken = $tokenSet->getAccessToken();   // Access token
        $claims = $tokenSet->claims();                // IdToken claims (if id_token is available)
        $refreshToken = $tokenSet->getRefreshToken(); // Refresh token
        $exp = $tokenSet->getExpiresIn();

        echo "<b>Token set</b><br>";       print_r($tokenSet); echo "<br>";
        echo "<b>Claims</b><br>";          print_r($claims); echo "<br>";
        echo "<b>ID token</b><br>";        print_r($idToken); echo "<br>";
        echo "<b>Access token</b><br>";    print_r($accessToken); echo "<br>";
        echo "<b>Refresh token</b><br>";   print_r($refreshToken); echo "<br>";
        echo "<b>Expires in</b><br>";      print_r($exp); echo "<br>";
        echo "<b>Code</b><br>";            print_r($code); echo "<br>";
        echo "<b>Token type</b><br>";      print_r($type); echo "<br>";
        echo "<b>State</b><br>";           print_r($state); echo "<br>";

        // Get user info
        //$userInfoService = (new UserInfoServiceBuilder())->build();
        //$userInfo = $userInfoService->getUserInfo($client, $tokenSet);
        //echo "<b>userInfo</b><br>";   print_r($userInfo); echo "<br>";

        die('<br>lalala');


        // https://github.com/facile-it/php-openid-client/blob/ef61cfb321bf323c9dcacd466ca609297ed28cfa/src/Service/AuthorizationService.php#L214
        // https://github.com/facile-it/php-openid-client/blob/ef61cfb321bf323c9dcacd466ca609297ed28cfa/src/Service/AuthorizationService.php#L244

        // Refresh the token
        // This fails with `Invalid token provided, The following 
        // claims are mandatory: at_hash. (0)`, when using just the code response type.


        $tokenSet = $authorizationService->refresh($client, $refreshToken);
        die('lala');
    }



    public function enforcer(Request $request, Response $response, array $args = []): Response {

        function pprint($d1, $d2 = "") {
            if (is_array($d1) or is_object($d1))  { print("<pre>".print_r($d1,true)."</pre>"); }
            else print($d1.'<br>');
            if (is_array($d2) or is_object($d2))  { print("<pre>".print_r($d2,true)."</pre>"); }
            else print($d2.'<br>');
        }

        $e = $this->enforcer;
        $m = $e->getModel();
  
        
        pprint( '<b>Domains relationsips</b>',                      $this->auth->get_domains() );
        pprint( '<b>Roles definitions</b>',                         $this->auth->get_roles() );
        pprint( '<b>Roles definitions having domain industra</b>',  $this->auth->get_roles_with_domain('industra') );
        pprint( '<b>Roles definitions having role usage</b>',       $this->auth->get_roles_with_role('usage') );
        pprint( '<b>Roles definitions having user pavel</b>',       $this->auth->get_roles_with_user('pavel') );
        pprint( '<b>Permissions</b>',                               $this->auth->get_permissions() );
        pprint('<b>Permissions for subject (role or user)</b>', $this->auth->get_permissions_for_subject('r:usage')); // add role/user differentiation
        pprint('<b>Permissions for subject (role or user) in domain</b>', $this->auth->get_permissions_for_subject_in_domain('r:usage', 'stage')); // add role/user differentiation
        pprint('======================', '');       
        pprint($e->getImplicitRolesForUser('u:pavel','stage'));
        pprint('======================');
        pprint('test1'); // policy for a role
        pprint($e->getFilteredPolicy(0, "r:usage"));
        pprint('test1'); // policy for a domain
        pprint($e->getFilteredPolicy(1, "*"));
        pprint('test2'); // policy for a resource
        pprint($e->getFilteredPolicy(2, "contacts"));
        pprint('$e->getRolesForUserInDomain("kuba", "industra") (user 2 in domain 0)');
        pprint($e->getRolesForUserInDomain("kuba", "industra"));

        pprint('$e->getFilteredGroupingPolicy(0, "kuba"); all role inheritance rules');
        pprint($e->getFilteredGroupingPolicy(0, "kuba"));
        //You can gets all the role inheritance rules in the policy, field filters can be specified. Then use array_filter() to filter.
        //Getting all domains that user is in
       

        //doesnt work, probably because of domain
        pprint($e->getRolesForUser("kuba", "industra"));
            //$r = $e->enforce((string)$sub, (string)$dom, (string)$obj, (string)$act); 

        $x = $e->enforce('jirka', 'industra', '/contacts', 'read') ? 'true' : 'false';  
        pprint('enforce:' . $x);
        $x = (bool) $e->enforce('jirka', 'stage', '/contacts', 'read') ? 'true' : 'false';  
        pprint('enforce:' . $x);

        
        echo $e->addRoleForUser("kubak", "r:admin","industra") ? 't': 'f';
        echo $e->addPermissionForUser('member', 'industra', '/foo', 'read')? 't': 'f';
        echo $e->addPolicy('eve', 'domain', 'data3', 'read') ? 't': 'f';
        echo $e->addPolicy('steve', 'domain', 'data3', 'read') ? 't': 'f';
                            $e->savePolicy();
        return $response;
    }


}
