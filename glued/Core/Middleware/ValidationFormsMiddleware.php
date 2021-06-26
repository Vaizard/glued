<?php

namespace Glued\Core\Middleware;
use Glued\Core\Middleware\AbstractMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * This class takes the validation_errors and validation_reseed session
 * variable (filled by the Core\Validation\Validator class) and passes
 * them on to the twig view as a global variable.
 *
 * See example usage in Core\Views\signup.twig
 * Note that the password is not reseeded intentionally!
 */
class ValidationFormsMiddleware extends AbstractMiddleware
{
    public function __invoke(Request $request, Handler $handler)
    {
        if (isset($_SESSION['validation_errors'])) {
            $this->view->getEnvironment()->addGlobal('validation_errors', $_SESSION['validation_errors'] ?? null);
            unset($_SESSION['validation_errors']); 
        }
        $this->view->getEnvironment()->addGlobal('validation_reseed', $_SESSION['validation_reseed'] ?? null);
        $_SESSION['validation_reseed'] = $request->getParams();
        return $handler->handle($request);
    }
}
