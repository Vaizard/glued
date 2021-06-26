<?php

declare(strict_types=1);

namespace Glued\Core\Classes\Error;

use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\ErrorRendererInterface;
use Throwable;

final class HtmlErrorRenderer
{
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
      

        $title = 'Error ' . $exception->getCode();
        $message = $exception->getMessage();

        if (($exception->getCode() === 404) and ($exception->getMessage() === 'Not found.')) { 
            $title = 'Error ' . $exception->getCode();
            $message = 'Page not found.';
        }
     
        return $this->renderHtmlPage($title, $message);
    }

    public function renderHtmlPage(string $title = '', string $message = ''): string
    {
        $title = htmlentities($title, ENT_COMPAT|ENT_HTML5, 'utf-8');
        $message = htmlentities($message, ENT_COMPAT|ENT_HTML5, 'utf-8');

        return <<<EOT
<!DOCTYPE html>
<html>
<head>
  <title>$title - My website</title>
    <link rel="stylesheet" type="text/css" href="/assets/cache/styles.0628d9652243c4ecba3533db540fcd7a2f6f439d.css" media="all" />
<body>
  <div style="padding: 20px;">
  <h1>$title</h1>
  <p>$message</p>
  </div>
</body>
</html>
EOT;
    }
}