<?php

declare(strict_types=1);

namespace Glued\Core\Classes\Auth;
use ErrorException;
use Firebase\JWT\JWT;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Respect\Validation\Validator as v;
use Sabre\Event\emit;
use UnexpectedValueException;

/**
 * Authentication
 *
 * Glued's authentication is twofold:
 * 
 * - session based
 * - jwt token based
 *
 * We keep sessions around since we're afraid to deal with the fallout.
 * Some components (i.e. slim-flash) rely on sessions, so we can't
 * get rid of them easily wihtout thinking. Going completely stateless
 * is also quite an endavour (i.e. token invalidation mechanisms, etc.). 
 * We use jwt to get state of the art authentication.
 * 
 * Users using browsers will always get 
 *
 * - a session cookie
 * - a jwt token (stored in a cookie)
 *
 * Users accessing the api directly will get only
 *
 * - the jwt token (sent in the response body)
 *
 * The session authentication middleware is configured to require a valid
 * session on all private routes and all api routes with the exception of
 * the signup and signin page routes. The jwt authentication middleware is 
 * configured to to require a valid jwt token sent as either a header or 
 * a cookie on all api routes with the exception of the signin api route.
 *
 * The middlewares set the $request attributes which are accessible through
 * the $request->getattribute('auth') array. First executes the jwt 
 * middleware, later the session middleware.  
 *
 * TODO: replace $_SESSION everywhere with $request->getattribute('auth')
 * 
 */

class Auth
{

    protected $settings;
    protected $db;
    protected $logger;
    protected $events;

    public function __construct($settings, $db, $logger, $events, $enforcer) {
        $this->db = $db;
        $this->settings = $settings;
        $this->logger = $logger;
        $this->events = $events;
        $this->e = $enforcer;
        $this->m = $this->e->getModel();
    }

    //////////////////////////////////////////////////////////////////////////
    // JWT HELPERS ///////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////

    public function get_domains() {
        return $this->m->getPolicy('g', 'g2');
    }

    public function get_roles() {
        return $this->m->getPolicy('g','g');
    }

    public function get_roles_with_domain(string $domain) {
        return $this->m->getFilteredPolicy('g', 'g', 2, (string) $domain);
    }

    public function get_roles_with_role(string $role) {
        return $this->m->getFilteredPolicy('g', 'g', 1, 'r:' . (string) $role);
    }
    public function get_roles_with_user(string $user) {
        return $this->m->getFilteredPolicy('g', 'g', 0, 'u:' . (string) $user);
    }

    public function get_permissions() {
        return $this->e->getPolicy('p', 'p');
    }

    public function get_permissions_for_subject(string $sub) {
        return $this->e->getFilteredPolicy(0, $sub);
    }

    public function get_permissions_for_subject_in_domain(string $sub, string $dom) {
        return $this->e->getFilteredPolicy(0, $sub, $dom);
    }

    public function get_permissions_for_user($string) {
        return $this->m->getFilteredPolicy('p','p', 0,'r:usage');
    }

    public function get_permissions_for_domain($string) {
        return $this->m->getFilteredPolicy('p','p', 0,'r:usage');
    }

    public function get_permissions_for_object($string) {
        return $this->m->getFilteredPolicy('p','p', 0,'r:usage');
    }
   

    public function getroutes() :? array {
        $routes = $app->getContainer()->router->getRoutes();
        $list=array();
        foreach ($routes as $route) {
            $list[]= $route->getPattern() .' '. json_encode($route->getMethods());
          }
        print_r($list);
    }

    public function safeAddPolicy(object $e, object $m, string $section, string $type, array $rule) {
        if (!$m->hasPolicy($section, $type, $rule)) {
            $m->addPolicy($section, $type, $rule);  
            $e->savePolicy();
        }
    }

    


    public function user_list() :? array {
        // replace with attribute filtering
        // $this->db->where("c_uid", $user_id); 
        return $this->db->get("t_core_users");
    }




}

