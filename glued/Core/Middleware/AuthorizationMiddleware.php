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


/**
 * Deals with RBAC/ABAC
 */
final class AuthorizationMiddleware extends AbstractMiddleware implements MiddlewareInterface

{
    private function merge_authn(ServerRequestInterface $request): void {
        $ses = $_SESSION ?? null;
        $jwt = $request->getAttribute($this->settings['auth']['jwt']['attribute']) ?? null;

        $isvalid = true;
        $user_id = null;
        $auth_id = null;

        if (is_array($jwt) and !empty($jwt)) {
            $user_id = $jwt['g_uid'] ?? null;
            $auth_id = $jwt['g_aid'] ?? null;
        }
        if (is_array($ses) and !empty($ses)) {
            $user_id = $ses['core_user_id'] ?? null;
            $auth_id = $ses['core_auth_id'] ?? null;
        }
        if (is_array($jwt) and !empty($jwt) and is_array($ses) and !empty($ses)) {
            if (($ses['core_user_id'] ?? null) !== ($jwt['g_uid'] ?? null)) $user_id = null; 
            if (($ses['core_auth_id'] ?? null) !== ($jwt['g_uid'] ?? null)) $user_id = null;
        }

        if (!(v::intVal()->positive()->between(1, 4294967295)->validate($user_id))) $isvalid = false;
        if (!(v::intVal()->positive()->between(1, 4294967295)->validate($auth_id))) $isvalid = false;

        if ($isvalid === true) {
            $GLOBALS['_JWT'] = $jwt;
            $GLOBALS['_GLUED']['authn'] = [
                'success' => null,
                'user_id' => $user_id,
                'auth_id' => $auth_id,
                'object'  => null
            ];
        }
    }


    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Set global variables used everywhere
        $this->merge_authn($request);
        $e = null;

        if ($this->auth->check() === true) {
            $user_authn = $this->auth->fetch();
            if ($user_authn) {
                $GLOBALS['_GLUED']['authn']['success'] = true;
                $GLOBALS['_GLUED']['authn']['object']  = $user_authn;
                $user_authn['success'] = true;
                $this->view->getEnvironment()->addGlobal('authn', $user_authn);
                $e = $this->enforcer;
            }
        }

        // TODO skip this for api paths $this->settings->auth->jwt->path
        $this->view->getEnvironment()->addFunction(new TwigFunction('enforce', function ($obj, $dom = "0", $sub = null, $act = "r") use ($e) {
            if (is_null($e)) return false;
            $sub = $GLOBALS['_GLUED']['authn']['user_id'] ?? null;
            $m = $e->getModel();
            $r = $e->enforce((string)$sub, (string)$dom, (string)$obj, (string)$act); 
            return $r;
        }));

        if ($this->auth->check() === true) {
            // AUTHORIZATION
            // TODO Uncommenting the stuff below is viable once problems with casbin are resolved

            //$e = $this->enforcer;
            //$m = $e->getModel();
            //getPolicy(string $sec, string $ptype):
            //print_r($m->getPolicy('g','g'));
            //print_r($m->getFilteredPolicy('g','g',1,'admin'));
            //die();
            //$f = $m::loadFunctionMap(); // ok
            //$f = $e->getRoleManager(); // ok
            //$f = $e->getPolicy();
            //$f = $e->getFilteredPolicy(0, '1'); 
            //$f = $e->getFilteredPolicy(1, '0');
            //$f = $e->getFilteredPolicy(2, '/ui/stor');
            //$f = $m->getFilteredPolicy('p','p',2, '/ui/stor');
            //$f = $e->getFilteredGroupingPolicy(0, '1');
            //$f = $e->getRolesForUser('2');
            //$f = $e->getRolesForUser('2');
            // print("<pre>".print_r($f,true)."</pre>");

            // TODO: we should support auth_id in the enforcing so that users can fine tune access control for different credentials
            //$sub = $GLOBALS['_GLUED']['authn']['user_id']; // the user that wants to access a resource.
            //$obj = "data1"; // the resource that is going to be accessed.
            //$act = "read"; // the operation that the user performs on the resource.
            //$e->enforce($u, '0', 'add-correct-route-here', 'r');  

            // $e->name, or $m->name?
            //print_r( $m->getPolicy(1,1,'all','read') ) ;
            //$e->addRoleForUser('alice', 'admin'); 
            //$e->addPermissionForUser('member', '/foo', 'GET');
            //$e->addPolicy('eve', 'data3', 'read');
            //$e->getRolesForUser('alice');

        }
        return $handler->handle($request);
    }
}

