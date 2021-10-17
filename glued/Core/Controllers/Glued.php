<?php

declare(strict_types=1);

namespace Glued\Core\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Glued\Core\Classes\Users;
use Glued\Core\Classes\Utils;
use Slim\Routing\RouteContext;


class Glued extends AbstractTwigController
{
    /**
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response, array $args = []): Response {
        $routes = $this->utils->get_navigation( $this->utils->get_current_route($request) );
        return $this->render($response, 'Core/Views/pages/main.twig', [ 'routes' => $routes ]);
    }


    /**
     * Unsets the OIDC cookie. The whole signout process consists of:
     * - Unsetting the access_token, refresh_token and id_token from LocalStorage
     *   via a javascript onclick event on the signout button (authentica.js)
     * - Destroying user's session on the identity server by
     *   letting authentica.js to redirect client to the id server's logout endpoint
     * - Unsetting the OIDC cookie by
     *   letting the id server redirect to the endpoint serviced by this function
     * - Redirecting to the glued instances homepage.
     */
    public function signout(Request $request, Response $response, array $args = []): Response {
        $arr_cookie_options = array (
                        'expires' => time() - 360000,
                        'path' => $this->settings['oidc']['cookie_param']['path'],
                        'secure' => $this->settings['oidc']['cookie_param']['secure'],
                        'samesite' => $this->settings['oidc']['cookie_param']['samesite']
                    );
        setcookie($this->settings['oidc']['cookie'], '', $arr_cookie_options);   
        return $response->withRedirect($this->settings['glued']['protocol'] . $this->settings['glued']['hostname']);
    }


    public function signin(Request $request, Response $response, array $args = []): Response {
        $caller = '';
        if ($enc = $request->getQueryParam('caller', $default = null)) {
            $caller = $this->crypto->decrypt( $enc , $this->settings['crypto']['reqparams'] );
        }
        return $this->render($response, 'Core/Views/pages/auth.twig', [ 'caller' => $caller ]);
    }

//INSERT INTO t VALUES(UUID_TO_BIN(UUID(), true));”
// Add data to tokens
// https://id.industra.space/auth/admin/master/console/#/realms/t1/clients/75cdbe25-7c19-4fba-8a70-ddf629011d39/add-mappers
// Add metadata to registration
// https://keycloakthemes.com/blog/how-to-add-custom-field-keycloak-registration-page
// https://github.com/keycloak/keycloak/tree/master/examples/themes
// actions
// https://id.industra.space/auth/admin/master/console/#/realms/t1/clients/75cdbe25-7c19-4fba-8a70-ddf629011d39/authz/resource-server/scope
// resources
// https://id.industra.space/auth/admin/master/console/#/realms/t1/clients/75cdbe25-7c19-4fba-8a70-ddf629011d39/authz/resource-server/resource
// https://id.industra.space/auth/admin/master/console/#/realms/t1/clients/75cdbe25-7c19-4fba-8a70-ddf629011d39/authz/resource-server/resource
// policies assign users/roles to actions and resources.
// assigning a user to a tenant/role tuple could be done by
// - assigning a user to group
// - defining first-level group as role
// - defining 2nd and nth level groups as groups (and subgroups)
// 
// 




}
