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

    public function keycloak_adm($request, $response) {
        echo "<b>"."https://github.com/MohammadWaleed/keycloak-admin-client"."</b>";
        $client = $this->oidc_adm;
        echo "<br><b>".'$client->getUsers()'."</b>";
        print("<pre>".print_r($client->getUsers(),true)."</pre>");
        return $response;
    }

    public function keycloak_signin($request, $response) {
        $settings = [];
        //$settings['login_hint']  = 'user_username';
        $settings['nonce']         = 'somenonce';
        $settings['response_mode'] = 'query';
        $settings['response_type'] = 'code';
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
        //$settings['response_type'] = 'code id_token token';
        //$settings['nonce'] = 'somenonce';
        $client = $this->oidc_cli;
        $authorizationService = $this->oidc_svc;
        $callbackParams = $authorizationService->getCallbackParams($request, $client, $settings);
        $tokenSet = $authorizationService->callback($client, $callbackParams);

        $idToken = $tokenSet->getIdToken();           // Unencrypted id_token
        $accessToken = $tokenSet->getAccessToken();   // Access token
        $claims = $tokenSet->claims();                // IdToken claims (if id_token is available)
        $refreshToken = $tokenSet->getRefreshToken(); // Refresh token
        $exp = $tokenSet->getExpiresIn();

        echo "<b>Callback params</b><br>"; print_r($callbackParams); echo "<br>";
        echo "<b>Token set</b><br>";       print_r($tokenSet); echo "<br>";
        echo "<b>Claims</b><br>";          print_r($claims); echo "<br>";
        echo "<b>ID token</b><br>";        print_r($idToken); echo "<br>";
        echo "<b>Access token</b><br>";    print_r($accessToken); echo "<br>";
        echo "<b>Refresh token</b><br>";   print_r($refreshToken); echo "<br>";
        echo "<b>Expires in</b><br>";      print_r($exp); echo "<br>";

        // Get user info
        //$userInfoService = (new UserInfoServiceBuilder())->build();
        //$userInfo = $userInfoService->getUserInfo($client, $tokenSet);
        //echo "<b>userInfo</b><br>";   print_r($userInfo); echo "<br>";

        die('<br>lala');


        // https://github.com/facile-it/php-openid-client/blob/ef61cfb321bf323c9dcacd466ca609297ed28cfa/src/Service/AuthorizationService.php#L214
        // https://github.com/facile-it/php-openid-client/blob/ef61cfb321bf323c9dcacd466ca609297ed28cfa/src/Service/AuthorizationService.php#L244

        // Refresh the token
        // This fails with `Invalid token provided, The following 
        // claims are mandatory: at_hash. (0)`, when using just the code response type.

        $tokenSet = $authorizationService->refresh($client, $refreshToken);
        die('lala');
    }

    public function keycloak_signout($request, $response) {
        $client = $this->oidc_cli;
        $authorizationService = $this->oidc_svc;
        $revocationService = (new RevocationServiceBuilder())->build();
        $callbackParams = $authorizationService->getCallbackParams($request, $client);
         $tokenSet = $authorizationService->callback($client, $callbackParams);
        $params = $revocationService->revoke($client, $tokenSet->getRefreshToken());
        return $response;
    }


}