<?php
declare(strict_types=1);
namespace Glued\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HeadersMiddleware implements MiddlewareInterface
{
    protected $settings;
    public function __construct($settings) 
    {
        $this->settings = $settings['headers'];
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $final = '';
        foreach ($this->settings['feature-policy'] as $key => $val) { $final .= $key.' '.$val.'; '; }
        $final = rtrim($final, '; ');

        $response = $handler->handle($request);
        return $response->withHeader('Feature-Policy', $final)
                        ->withAddedHeader('X-Content-Type-Options', $this->settings['content-type-options'])
                        ->withAddedHeader('Referrer-Policy', $this->settings['referrer-policy']);
    }
}