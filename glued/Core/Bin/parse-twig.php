<?php
//
// Twig to PHP compiler.
//
// Usage: php glued/Core/Bin/parse-twig.php
//
// Run `apt install poedit` or equivalent on your distro
// Start Poedit and:
// - either open the file ./private/locale/*.po (or equivalent to `$settings['locale']['path']`)
// - or click "New" to add a new po file to ./private/locale
// Open menu: Catalog > Properties > Source Path
// Add source path: ./private/cache/twig (or equivalent to `$settings['twig']['cache']`) - yep, translations are generated from cache files (possibly to get the php code equivalent)
//
// Open tab: Sources keywords
// Add keyword: __
// Click 'Ok' to store the settings
//
// Click button 'Update form source' to extract the template strings.
// Translate the text and save the file.
// Run this script.
//

use Odan\Twig\TwigCompiler;
use Twig\Environment as TwigEnv;
use Slim\Views\Twig;

require __DIR__ . '/../../bootstrap.php';

// Requires relative uri due to the trim() in TwigCompiler's constructor.
// Will change '/absolute/path' to 'absolute/path' and warn with path not found.
$cachePath = (string) $settings['twig']['cache']; 
$cachePath = (string) "./private/cache/twig/"; 

$twig = $container->get('view');
$compiler = new TwigCompiler($twig->getEnvironment(), $cachePath, true);
$compiler->compile();

echo "Done\n";
return 0;

/* Original code
https://github.com/odan/slim4-skeleton/blob/master/bin/parse-twig.php
*/