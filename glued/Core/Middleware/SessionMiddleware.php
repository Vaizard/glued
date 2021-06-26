<?php
namespace Glued\Core\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Dflydev\FigCookies\Cookie;
use Dflydev\FigCookies\FigResponseCookies;

final class SessionMiddleware implements MiddlewareInterface
{

    //protected $settings;
    protected $options;

    public function __construct($settings) 
    {
        //$this->settings = $settings;
        $ini_defs = session_get_cookie_params();
        $options = [
            'session' => [
                'name'         => $this->settings['session']['cookie_name'] ?? 'g_sid',
                'save_handler' => $this->settings['session']['save_handler'] ?? 'files',
                'save_path'    => $this->settings['session']['save_path'] ?? null,
                'callback'     => $this->settings['session']['callback'] ?? null,
            ],
            'cookie' => [
                'lifetime' => $this->settings['cookie']['lifetime'] ?? 0,
                'path'     => $this->settings['cookie']['path'] ?? $ini_defs['path'],
                'domain'   => $this->settings['cookie']['domain'] ?? $ini_defs['domain'],
                'secure'   => $this->settings['cookie']['secure'] ?? $ini_defs['secure'],
                'httponly' => $this->settings['cookie']['httponly'] ?? $ini_defs['httponly'],
                'samesite' => $this->settings['cookie']['samesite'] ?? $ini_defs['samesite'],
            ],
        ];
        $this->options = $options;
    }
    
    /**
     * Invokes this middleware.
     * @param ServerRequestInterface $request The request
     * @param RequestHandlerInterface $handler The handler
     * @return ResponseInterface The response
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (ini_get('session.use_cookies')) session_set_cookie_params($this->options['cookie']);
            session_name($this->options['session']['name']); 
            session_start();
        }
        
        // Callback before calling the next middleware
        if (is_callable($this->options['session']['callback'])) {
            $result = $this->options['session']['callback']();
        }

        $response = $handler->handle($request);
        session_write_close();
        return $response;
    }
}