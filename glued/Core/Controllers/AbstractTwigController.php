<?php

declare(strict_types=1);

namespace Glued\Core\Controllers;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

class AbstractTwigController extends AbstractController
{
    /**
     * Render the template and write it to the response.
     *
     * @param Response $response
     * @param string   $template
     * @param array    $renderData
     *
     * @return Response
     */
    protected function render(Response $response, string $template, array $renderData = []): Response
    {
        return $this->view->render($response, $template, $renderData);
    }
}
