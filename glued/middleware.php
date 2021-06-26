<?php

use DI\Container;
use Glued\Core\Classes\Error\HtmlErrorRenderer;
use Glued\Core\Middleware\HeadersMiddleware;
use Glued\Core\Middleware\LocaleSessionMiddleware;
use Glued\Core\Middleware\SessionMiddleware;
use Glued\Core\Middleware\Timer;
use Glued\Core\Middleware\TranslatorMiddleware;
use Glued\Core\Middleware\TwigCspMiddleware;
use Glued\Core\Middleware\TwigFlashMiddleware;
use Middlewares\Csp;
use Middlewares\TrailingSlash;
use Nyholm\Psr7\Response as Psr7Response;
use ParagonIE\CSPBuilder\CSPBuilder;
use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Slim\Middleware\ErrorMiddleware;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\addRoutingMiddleware;
use Tuupola\Middleware\CorsMiddleware;
use Zeuxisoo\Whoops\Slim\WhoopsMiddleware;
use Facile\OpenIDClient\Middleware\SessionCookieMiddleware;

/**
 * WARNING
 * 
 * Middleware in Slim 4 are executed in the reverse order as they appear in middleware.php.
 * Do not change the order of the middleware below without a good thought. The first middleware
 * to kick must always be the error middleware, so it has to be at the end of this file.
 * 
 */

$app->add(TwigMiddleware::createFromContainer($app));
$app->add(TranslatorMiddleware::class);
$app->add(LocaleSessionMiddleware::class);
$app->add(Timer::class); // adds time needed to generate a response to headers
$app->addBodyParsingMiddleware();



 // BodyParsingMiddleware detects content-type set to a JSON or XML media type
 // and automatically decodes getBody() into a php array and places the decoded body
 // into the Request’s parsed body property.
$app->addRoutingMiddleware();



/**
 * *******************************
 * TRAILING SLASH MIDDLEWARE
 * *******************************
 * 
 * TrailingSlash(false) means trailing slash is disabled (i.e. https://example.com/user)
 * redirect(true) enforces a 301 redirect from https://example.com/user/ to https://example.com/user
 */
$trailingSlash = new TrailingSlash(false);
$trailingSlash->redirect();
$app->add($trailingSlash);

$app->add(\Glued\Core\Middleware\ValidationFormsMiddleware::class);

// TODO: consider joining the TwigFlashMilldeware, TwigCSPMilldeware
// and AuthMiddleware into a single middleware TwigGlobalsMiddleware.
// We're only setting twig globals in all three so, why not, eh?
//$app->add(new \Glued\Core\Middleware\TwigFlashMiddleware($container)); 

$csp = new CSPBuilder($settings['headers']['csp']);
$nonce['script_src'] = $csp->nonce('script-src');
$nonce['style_src'] = $csp->nonce('style-src');
$app->add(new Middlewares\Csp($csp));
$app->add(new \Glued\Core\Middleware\TwigCspMiddleware($nonce, $container));
$app->add(new Tuupola\Middleware\CorsMiddleware); // TODO add sane defaults to CorsMiddleware
$app->add(new HeadersMiddleware($settings));
$app->add(new \Glued\Core\Middleware\AuthorizationMiddleware($container));
//$app->add(new SessionMiddleware($settings));
$app->add(new SessionCookieMiddleware($container->get('fscache')/* , $cookieName = "openid", $ttl = 300 */));
$app->add(new Tuupola\Middleware\JwtAuthentication($settings['auth']['jwt']));




/**
 * *******************************
 * METHOD OVERRIDE MIDDLEWARE
 * *******************************
 *
 * Per the HTML standard, desktop browsers will only submit GET and POST requests, PUT
 * and DELETE requests will be handled as GET. This is middleware allows desktop browsers
 * to submit pseudo PUT and DELETE requests by relying on pre-determined request 
 * parameters (either a `X-Http-Method-Override` header, or a `_METHOD` form value) 
 * allowing routing unification. 
 * 
 * This middleware must be added last must be added before `$app->addRoutingMiddleware();`
 */

$app->add(new MethodOverrideMiddleware); // Add this before `$app->addRoutingMiddleware();`

/**
 * *******************************
 * ERROR HANDLING MIDDLEWARE
 * *******************************
 * 
 * This middleware must be added last. It will not handle any exceptions/errors
 * for middleware added after it.
 */

if ($settings['debugEngine'] == "Whoops") {

    $app->add(new Zeuxisoo\Whoops\Slim\WhoopsMiddleware([
        'enable' => true,
        'editor' => 'sublime',
        'title'  => 'Custom whoops page title',
    ]));

} else {

    /**
     * @param bool $displayErrorDetails -> Should be set to false in production
     * @param bool $logErrors -> Parameter is passed to the default ErrorHandler
     * @param bool $logErrorDetails -> Display error details in error log
     * which can be replaced by a callable of your choice.
     */
    $errorMiddleware = new ErrorMiddleware(
        $app->getCallableResolver(),
        $app->getResponseFactory(),
        $settings['displayErrorDetails'],
        $settings['logErrors'],
        $settings['logErrorDetails']
    );

    if ($settings['displayErrorDetails'] === false) {
        $errorHandler = $errorMiddleware->getDefaultErrorHandler();
        $errorHandler->registerErrorRenderer('text/html', HtmlErrorRenderer::class);
        // TODO beautify html renderer
        // TODO review json renderer & modify if needed
        // HELP https://akrabat.com/custom-error-rendering-in-slim-4/
    }

    $app->add($errorMiddleware);

    /*
    // Example 404 override. Usage: `throw new HttpNotFoundException($request, 'optional message');`
    // Must be placed above the $app->add();
    $errorMiddleware->setErrorHandler(HttpNotFoundException::class, function ($request, $exception, $displayErrorDetails, $logErrors, $logErrorDetails) use ($container) {
        $response = new Psr7Response();
        return $container->get('view')->render(
            $response->withStatus(404), 
            'Core/Views/errors/404.twig'
        );
    });
    */
   
}







