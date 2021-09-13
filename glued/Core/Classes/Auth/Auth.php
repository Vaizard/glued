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
use Glued\Core\Classes\Exceptions\AuthTokenException;
use Glued\Core\Classes\Exceptions\AuthOidcException;
use Glued\Core\Classes\Exceptions\DbException;
use Jose\Component\Core\JWK;


/**
 * Provides authentication and authorization methods
 */

class Auth
{

    protected $settings;
    protected $db;
    protected $logger;
    protected $events;

    public function __construct($settings, $db, $logger, $events, $enforcer, $fscache, $utils) {
        $this->db = $db;
        $this->settings = $settings;
        $this->logger = $logger;
        $this->events = $events;
        $this->e = $enforcer;
        $this->m = $this->e->getModel();
        $this->fscache = $fscache;
        $this->utils = $utils;
    }

    //////////////////////////////////////////////////////////////////////////
    // AUTHORIZATION METHODS /////////////////////////////////////////////////
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

    //////////////////////////////////////////////////////////////////////////
    // AUTHENTICATION METHODS ////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////

    public function get_jwks(): array {
        $oidc = $this->settings['oidc'];
        $hit = $this->fscache->has('glued_oidc_uri_discovery');
        if ($hit) {
            $conf = (array) json_decode($this->fscache->get('glued_oidc_uri_discovery') ?? []);
            if ($conf['issuer'] != $oidc['uri']['realm']) $hit = false;
        }

        if (!$hit) {
            $json = (string) $this->utils->fetch_uri($oidc['uri']['discovery']) ?? ''; 
            $conf = (array) json_decode($json);
            if ($conf == []) throw new AuthOidcException('Identity backend returned empty resultset, backend down?');
            if ($conf['issuer'] != $oidc['uri']['realm']) throw new AuthOidcException('Identity backend configuration mismatch.');
            $this->fscache->set('glued_oidc_uri_discovery', $json, 300); // TODO make the 300s value configurable
        }

        $hit = $this->fscache->has('glued_oidc_uri_jwks');
        if ($hit) {
            $conf = (array) json_decode($this->fscache->get('glued_oidc_uri_jwks') ?? []);
            if (!isset($jwks['keys'])) $hit = false;
        }

        if (!$hit) {
            $json = (string) $this->utils->fetch_uri($oidc['uri']['jwks']) ?? '';
            $jwks = (array) json_decode($json);
            if ($conf == []) throw new AuthOidcException('Identity backend returned empty resultset., backend down?');
            if (!isset($jwks['keys'])) throw new AuthOidcException('Identity backend certs mismatch.');
            $this->fscache->set('glued_oidc_uri_jwks', $json, 300); // TODO make the 300s value configurable
        }

        $certs = [];
        foreach ($jwks['keys'] as $item) {
            $item = (array) $item;
            if ($item['use'] === 'sig') $certs[] = new JWK($item);
        }
        return $certs;
    }


    public function fetch_token($request) {
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
        throw new AuthTokenException("Token not found.");
    }
   
    //////////////////////////////////////////////////////////////////////////
    // OTHER AUTH RELATED METHODS ////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////

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

