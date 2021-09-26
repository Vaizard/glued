<?php

declare(strict_types=1);

namespace Glued\Core\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Glued\Core\Classes\Users;
use Glued\Core\Classes\Utils;


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
        $routes = $this->utils->get_navigation();
        return $this->render($response, 'Core/Views/pages/main.twig', [ 'routes' => $routes ]);
    }

    public function signin(Request $request, Response $response, array $args = []): Response
    {
        $caller = '';
        if ($enc = $request->getQueryParam('caller', $default = null)) {
                $caller = $this->crypto->decrypt( $enc , $this->settings['crypto']['reqparams'] );
        }

        return $this->render($response, 'Core/Views/pages/auth.twig', [
                'caller' => $caller,
                'hostname' => $this->settings['glued']['hostname'],
                'oidc_token' => $this->settings['oidc']['uri']['token'],
                'oidc_auth' => $this->settings['oidc']['uri']['auth'],
                'oidc_client' => $this->settings['oidc']['client']['public']['id'],
                'oidc_cookie_name' => $this->settings['oidc']['cookie'],
                'oidc_cookie_params' => $this->settings['oidc']['cookie_params'],
        ]);
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
