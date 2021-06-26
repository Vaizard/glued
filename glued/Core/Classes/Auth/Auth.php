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

    public function __construct($settings, $db, $logger, $events) {
        $this->db = $db;
        $this->settings = $settings;
        $this->logger = $logger;
        $this->events = $events;
    }

    //////////////////////////////////////////////////////////////////////////
    // JWT HELPERS ///////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////


    public function jwt_extend(array $decoded) : string {
        $exp = new \DateTime('+' . $this->settings['auth']['jwt']['expiry']);
        $decoded['exp'] = $exp->getTimeStamp();
        $token = $this->jwt_setcookie($decoded['sub'], $decoded);
        return $token;
    }


    /**
     * Creates a new jwt token
     * @param  string $sub  [description]
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function jwt_create(string $sub, array $data = []) : string {
        $now = new \DateTime();
        $exp = new \DateTime('+' . $this->settings['auth']['jwt']['expiry']);
        $payload = [
            'iss'   => $_SERVER['SERVER_NAME'],   // Issuer
            'iat'   => $now->getTimeStamp(),      // Issued at time
            'exp'   => $exp->getTimeStamp(),      // Expires
            'jti'   => uniqid(),
            'sub'   => $sub,
        ];
        $payload = array_merge($payload, $data);
        $token = JWT::encode($payload, $this->settings['auth']['jwt']['secret'], $this->settings['auth']['jwt']['algorithm']);
        return $token;
    }


    public function jwt_setcookie(string $sub, array $data = []) :? string {
        $token = $this->jwt_create($sub, $data);
        // We generally want to sync the validity of the cookie holding the JWT token to the validity
        // of the user session. We additionally want to keep the EXP payoad in the JWT short, 15 minutes
        // max and keep updating it according to user activity (this means we can get a valid JWT cookie
        // containing and expired JWT token).
        // TODO update expired JWT tokens in cookies if users session is still ok.
        if ($this->settings['auth']['cookie']['lifetime'] == 0) 
            $expires = 0;
        else 
            $expires = (new \DateTime('+' . $this->settings['auth']['jwt']['expiry']))->getTimeStamp();
        $opts = [
            'expires'  => $expires,
            'path'     => $this->settings['auth']['cookie']['path'] ?? '/api', //api
            'domain'   => $this->settings['auth']['cookie']['domain'] ?? null,
            'secure'   => $this->settings['auth']['cookie']['secure'],
            'httponly' => $this->settings['auth']['cookie']['httponly'], //false
            'samesite' => $this->settings['auth']['cookie']['samesite'],
        ];
        if (setcookie($this->settings['auth']['jwt']['cookie'], $token, $opts)) 
            return $token;
        else 
            return null;
    }

    //////////////////////////////////////////////////////////////////////////
    // LOGGING HELPERS ///////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////

    /**
     * This is just a monolog improvements placeholder / idea dump space.
     * TODO: replace $logger with a correct implementation according to ideas
     * presented here.
     * TODO: add custom processor according to
     * http://zetcode.com/php/monolog/ to add 'how' (geoip,ip,fp) 
     * and 'origin' (uid,aid) data.
     */
    public function log($request, $event, $details) : bool {
        $user_id = $GLOBALS['_GLUED']['authn']['user_id'] ?? false;
        $auth_id = $GLOBALS['_GLUED']['authn']['auth_id'] ?? false;
        //$fingerprint['ua'] = $request->getUserAgent();
        //$fingerprint['ua'] = $request->getUserAgent();
        $log = [ 
            'action' => 'core_auth_attempt',
            'result' => 1,
            'return' => null,
            'params' => [ 'email' => $email, 'passowrd' => $password ] ?? func_get_args(),
            'origin' => [ 'uid' => $user['c_user_uid'], 'aid' => $user['c_uid'] ],
            // how
            'geoip'  => '',
            'ip'     => '',
            'fp'     => '',
        ];
        return true;
    }

    /**
     * This is just a throttling stub.
     * TODO: replace the dummy $this->throttle() function
     */
    public function throttle($event, $details) : void {
        //
    }

    //////////////////////////////////////////////////////////////////////////
    // AUTHORIZATION ACTIONS /////////////////////////////////////////////////
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

    

    //////////////////////////////////////////////////////////////////////////
    // AUTHENTICATION ACTIONS ////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////

    /**
     * Attempts to sign in a user.
     * @param  string $email    User's e-mail.
     * @param  string $password User's password.
     * @return string/null      On success, the JWT token string is returned
     *                          and $_SESSION and $_GLUED superglobals are set.
     *                          On failure null is returned.
     */ 
    public function attempt($email, $password) :? string {
        $token = null;
        $log_context = [
            'action' => ACT_AUTH_ATTEMPT,
            'result' => 0,
            'params' => [ 'email' => $email ],
        ];
        $this->db->join("t_core_authn a", "a.c_user_uid=u.c_uid", "LEFT");
        $this->db->where("u.c_email", $email);
        $this->db->where("a.c_type", 0); // [ 0 => 'passwords', 1 => 'api keys' ]
        $result = $this->db->get("t_core_users u", null);
           if ($this->db->count > 0) {
            foreach ($result as $user) {
                if (password_verify($password, $user['c_hash'])) {
                    // 'Auth attempt successfull' code branch
                    $_SESSION = [
                        'core_user_id' => $user['c_user_uid'],
                        'core_auth_id' => $user['c_uid']
                    ];
                    $GLOBALS['_GLUED']['authn'] = [
                        'success' => true,
                        'user_id' => $user['c_user_uid'],
                        'auth_id' => $user['c_uid'],
                        'object'  => $user
                    ];
                    $token = $this->jwt_setcookie($email, [ 'g_uid' => $user['c_user_uid'], 'g_aid' => $user['c_uid'] ]);
                    $log_context['result'] = 1;
                    $this->logger->info('Auth attempt ok', $log_context);
                    return $token;
                }
            }
        }
        // 'Auth attempt failed' code branch
        $this->logger->error('Auth attempt failed', $log_context);
        $this->throttle(ACT_AUTH_ATTEMPT,'ip-address');
        return $token;
    }


    /**
     * Deletes session data server side and expires the 
     * session cookie client side
     * @return void
     */
    private function signout_session() : void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
            session_write_close();
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                $params['expires'] = time() - 40000;
                unset($params['lifetime']);
                setcookie(session_name(), '', $params);
            }
        }
    }

    /**
     * Unsets (expires) the JWT cookie if JWT
     * session cookie client side
     * @return void
     */
    private function signout_jwt() : void {
        // TODO test if JWT cookie is used
        $params = $this->settings['auth']['cookie'];
        $params['expires'] = time() - 40000;
        unset($params['lifetime']);
        setcookie($this->settings['auth']['jwt']['cookie'], '', $params);
        
        // TODO store signout time in database
        // TODO add an after() callback to to the JWT middlware
        // (or to the authorization middleware) that will reject
        // tokens with iat < last signout time from the database
        // for the given user (aud)
        $logout = microtime();    
    }


    /**
     * Signs out a user.
     * @return void
     */
    public function signout() : void {
        $this->signout_session();
        $this->signout_jwt();
    }


    /**
     * Checks if user has his [user_id, auth_id] pair either in the
     * session, or in the jwt token (or in both) and if the values
     * pass validation.
     * @return bool
     */
    public function check() : bool  {
        $user_id = $GLOBALS['_GLUED']['authn']['user_id'] ?? false;
        $auth_id = $GLOBALS['_GLUED']['authn']['auth_id'] ?? false;
        if (($user_id === false) or ($auth_id === false)) { return false; }
        return true;
    }


    public function reset($email)
    {
        $trx_error = false;
        $this->db->startTransaction();
        $data = array (
            'c_email' => $email,
        );
        if (!$this->db->insert ('t_core_users', $data)) { $trx_error = true; }
        $subq = $this->db->subQuery()->where('c_type', 0)->where('c_email', $email)->getOne('t_core_authn', 'c_uid as c_authn_uid, c_user_uid');
        $data = array (
            'c_type' => 0,
            'c_user_uid' => $subq['c_user_uid'],
            'c_user_authn' => $subq['c_authn_uid'],
            'c_token' => random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES),
            'c_ts_'
        );
        if (!$this->db->insert ('t_core_authn', $data)) { $trx_error = true; }
        if ($trx_error === true) { $this->db->rollback(); } 
        else { $this->db->commit(); }
    }


    /**
     * Checks ifGet response-modifying data.
     * (Auth context, personalization)
     * 
     * @param  [type]  $user_id [description]
     * @param  [type]  $auth_id [description]
     * @return [type]           [description]
     */
    public function fetch() {
        $user_id = $GLOBALS['_GLUED']['authn']['user_id'] ?? null;
        $auth_id = $GLOBALS['_GLUED']['authn']['auth_id'] ?? null;
        if (isset($user_id) and isset($user_id)) {
            // Fetch user's data 
            $columns = [ 
                "u.c_uid AS u_uid",
                "u.c_attr AS u_attr",
                "u.c_email AS u_email",
                "u.c_name AS u_name",
                "u.c_lang AS u_lang",
                "a.c_uid AS a_uid",
                "a.c_type AS a_type",
                "a.c_attr AS a_attr" 
            ];
            $this->db->join("t_core_authn a", "a.c_user_uid=u.c_uid", "LEFT");
            $this->db->where("u.c_uid", $user_id);
            $this->db->where("a.c_uid", $auth_id);
            $result = $this->db->getOne("t_core_users u", $columns);
            // Signout and throw error if no database match found
            if (!$result) {
                $this->signout();
                throw new ErrorException(__('Forbidden and signed out.'), 403);
            }
        } else {
            throw new ErrorException(__('Forbidden.'), 403);
        }
        return $result;
    }


    //////////////////////////////////////////////////////////////////////////
    // CREDENTIALS CRUD //////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////

    public function cred_create($uid, $password) {
        // TODO
        // add interface and function to supplement once user 
        // account with secondary login credentials, such as
        // a secondary (plausible deniability password) or an api
        // key, etc.
    }

    public function cred_delete($uid, $auth_id) {
        // TODO: disable an auth token/password
        // NOTE: do NOT delete disabled aith tokens/passwords.
        // the attempt() function should probe even old passwords
        // to identify IPs that should get rate-limited
    }

    public function cred_update($user_id, $auth_id, $password) {
        // TODO add password disabling part (we want to keep the old hash to honeypot bots)
        $this->db->where('c_type', 0);
        $this->db->where('c_uid', (int)$auth_id);
        $this->db->where('c_user_uid', (int)$user_id);
        $update = $this->db->update( 't_core_authn', [
            'c_hash' => password_hash($password, $this->settings['php']['password_hash_algo'], $this->settings['php']['password_hash_opts']) 
        ]);
        if (!$update) { return false; } else { return true; }
    }

    public function cred_list() :? array {
        // replace with attribute filtering
        // $this->db->where("c_uid", $user_id); 
        return $this->db->get("t_core_authn");
    }


    //////////////////////////////////////////////////////////////////////////
    // USERS CRUD ////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////

    /**
     * [user_create description]
     * @param  [type] $email    [description]
     * @param  [type] $name     [description]
     * @param  [type] $password [description]
     * @return [type]           [description]
     */
    public function user_create($email, $name, $password) :? int {
        $trx_error = false;
        $this->db->startTransaction();
        $data = [
            'c_email' => $email,
            'c_name'  => $name,
        ];

        $i1 = $this->db->insert('t_core_users', $data);
        if (!$i1) $trx_error = true;

        $subq = $this->db->subQuery()->where('c_email', $email)->getOne('t_core_users', 'c_uid');
        $data = [
            'c_type' => 0,
            'c_user_uid' => $subq,
            'c_hash' => password_hash($password, $this->settings['php']['password_hash_algo'], $this->settings['php']['password_hash_opts']),
        ];
        $i2 = $this->db->insert('t_core_authn', $data);
        if (!$i2) $trx_error = true;

        if ($trx_error === true) { 
            $this->db->rollback(); 
            return null; 
        } 
        if (!$this->db->commit()) return null;
        return $i2;
    } 

    /**
     * [user_read description]
     * @param  [type] $uid [description]
     * @return [type]      [description]
     */
    public function user_read($uid) : array { 
        if (!(v::intVal()->positive()->between(0, 4294967295)->validate($uid)))
            throw new UnexpectedValueException('Bad request (wrong user id).', 550);
        $this->db->where("c_uid", $uid);
        $result = $this->db->getOne("t_core_users");
        if (!$result)
            throw new UnexpectedValueException('Not found (no such user).', 450);
        return $result;
    }

    public function user_delete($uid) {
        /*
        DELETING ACCOUNTS & GDPR:
        - user wants to delete his/her account.
        - we should remove all data from t_core_authn to prevent new logins
        - we should set user's email in t_core_users to null (gdpr compliance)
        - we must keep the user's handle/screenname (so that others cant impersonate the user)
        - we must keep the row in t_core_users (so that data left behind by the user have a valid owner relationship)
        - we must set the attr in t_core_users to disabled, so that we can handle edge cases on application side
        PLAUSIBLE DENIABILITY
        - user may choose to have multiple login/password combinations and/or multiple api keys enabling devices access as well
        - each login/pass and api can have an assigned scope enabling/disabling access to selected data. This way, a user can
          hand out i.e. limited scope access credentials to criminals, or prying law enforcement in non-free regimes. Naturally
          this works only as long as the server instances you host dont get compromised too - this is meant to protect users
          such as investigators or journalists.
        */
    }

    public function user_list() :? array {
        // replace with attribute filtering
        // $this->db->where("c_uid", $user_id); 
        return $this->db->get("t_core_users");
    }




}

