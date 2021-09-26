<?php

use Alcohol\ISO4217;
use Casbin\Enforcer;
use Casbin\Util\BuiltinOperations;
use DI\Container;
use Facile\OpenIDClient\Client\ClientBuilder;
use Facile\OpenIDClient\Client\Metadata\ClientMetadata;
use Facile\OpenIDClient\Issuer\IssuerBuilder;
use Facile\OpenIDClient\Service\Builder\AuthorizationServiceBuilder;
use Glued\Core\Classes\Auth\Auth;
use Glued\Core\Classes\Utils\Utils;
use Glued\Core\Middleware\TranslatorMiddleware;
use Glued\Stor\Classes\Stor;
use Goutte\Client;
use GuzzleHttp\Client as Guzzle;
use Http\Discovery\Psr17FactoryDiscovery;
use Keiko\Uuid\Shortener\Dictionary;
use Keiko\Uuid\Shortener\Shortener;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Nyholm\Psr7\getParsedBody;
use Odan\Twig\TwigAssetsExtension;
use Odan\Twig\TwigTranslationExtension;
use Phpfastcache\CacheManager;
use Phpfastcache\Config\Config;
use Phpfastcache\Helper\Psr16Adapter;
use Sabre\Event\Emitter;
use Selective\Transformer\ArrayTransformer;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Translation\Loader\MoFileLoader;
use Symfony\Component\Translation\Translator;
use Twig\TwigFilter;
use voku\helper\AntiXSS;
use VStelmakh\UrlHighlight\UrlHighlight;
use VStelmakh\UrlHighlightTwigExtension\UrlHighlightExtension;




$container->set('events', function () {
    return new Emitter();
});

$container->set('settings', function() {
    $ret = [];
    foreach (glob(__ROOT__ . '/glued/*/Config/defaults.php') as $configfile) {
        $ret = array_merge_recursive($ret, require_once($configfile));
    }
    foreach (glob(__ROOT__ . '/config/config.d/*.php') as $configfile) {
        $ret = array_replace_recursive($ret, require_once($configfile));
    }
    return $ret;
});

$container->set('logger', function (Container $c) {
    define('ACT_AUTH_ATTEMPT', 1);
    $settings = $c->get('settings')['logger'];
    $logger = new Logger($settings['name']);
    $processor = new UidProcessor();
    $logger->pushProcessor($processor);
    $handler = new StreamHandler($settings['path'], $settings['level']);
    $logger->pushHandler($handler);
    return $logger;
});

$container->set('mysqli', function (Container $c) {
    $db = $c->get('settings')['db'];
    $mysqli = new mysqli($db['host'], $db['username'], $db['password'], $db['database']);
    $mysqli->set_charset($db['charset']);
    $mysqli->query("SET collation_connection = ".$db['collation']);
    return $mysqli;
});

$container->set('db', function (Container $c) {
    $mysqli = $c->get('mysqli');
    $db = new \MysqliDb($mysqli);
    return $db;
});

$container->set('fscache', function () {
        CacheManager::setDefaultConfig(new Config([
        "path" => '/var/www/html/glued-skeleton/private/cache/psr16',
        "itemDetailedDate" => false
      ]));
      return new Psr16Adapter('files');
});

$container->set('transform', function () {
    return new ArrayTransformer();
});

$container->set('uuid_base62', function () {
    $shortener = Shortener::make(
        Dictionary::createAlphanumeric() // or pass your own characters set
    );
    return $shortener;
});

$container->set('uuid_base57', function () {
    $shortener = Shortener::make(
        Dictionary::createUnmistakable() // or pass your own characters set
    );
    return $shortener;
});


$container->set('antixss', function () {
    return new AntiXSS();
});

$container->set('goutte', function () {
    return new Goutte\Client();
});

$container->set('jsonvalidator', function () {
    return new \Opis\JsonSchema\Validator;
});

$container->set('routecollector', $app->getRouteCollector());


/**
 * Casbin enforcer
 */
$container->set('enforcer', function (Container $c) {
    $s = $c->get('settings');
    $adapter = __ROOT__ . '/private/cache/casbin.csv';
    /*
    if ($s['casbin']['adapter'] == 'database')
        $adapter = DatabaseAdapter::newAdapter([
            'type'     => 'mysql',
            'hostname' => $s['db']['host'],
            'database' => $s['db']['database'],
            'username' => $s['db']['username'],
            'password' => $s['db']['password'],
            'hostport' => '3306',
        ]);*/
    $e = new Enforcer($s['casbin']['modelconf'], $adapter);

    $e->addNamedMatchingFunc('g', 'keyMatch2', function (string $key1, string $key2) {
        return BuiltinOperations::keyMatch2($key1, $key2);
    });
    $e->addNamedDomainMatchingFunc('g', 'keyMatch2', function (string $key1, string $key2) {
        return BuiltinOperations::keyMatch2($key1, $key2);
    });
    return $e;
});

$container->set('oidc_adm', function (Container $c) {
    $s = $c->get('settings')['oidc'];
    $client = \Keycloak\Admin\KeycloakClient::factory([
        'baseUri'   => $s['server'],
        'realm'     => $s['realm'],
        'client_id' => $s['client']['admin']['id'],
        'username'  => $s['client']['admin']['user'],
        'password'  => $s['client']['admin']['pass']
    ]);
    return $client;
});

$container->set('oidc_cli', function (Container $c) {
    $s = $c->get('settings')['oidc'];
    $issuer = (new IssuerBuilder())->build($s['uri']['discovery']);
    $clientMetadata = ClientMetadata::fromArray([
        'client_id'     => $s['client']['confidential']['id'],
        'client_secret' => $s['client']['confidential']['secret'],
        'token_endpoint_auth_method' => 'client_secret_basic', // the auth method to the token endpoint
        'redirect_uris' => $s['uri']['redirect']
    ]);
    $client = (new ClientBuilder())
        ->setIssuer($issuer)
        ->setClientMetadata($clientMetadata)
        ->build();
    return $client;
});

$container->set('oidc_svc', function (Container $c) {
    $s = $c->get('settings')['oidc'];
    $service = (new AuthorizationServiceBuilder())->build();
    return $service;
});


$container->set('view', function (Container $c) {
    $twig = Twig::create(__ROOT__ . '/glued/', $c->get('settings')['twig']);
    $loader = $twig->getLoader();
    $loader->addPath(__ROOT__ . '/public', 'public');
    $environment = $twig->getEnvironment();
    // Add twig exensions here
    $twig->addExtension(new TwigAssetsExtension($environment, (array)$c->get('settings')['assets']));
    $twig->addExtension(new TwigTranslationExtension($c->get(Translator::class)));
    $twig->addExtension(new \Twig\Extension\DebugExtension());
    $twig->addExtension(new \Twig\Extra\String\StringExtension());
    $urlHighlight = new UrlHighlight();
    $twig->addExtension(new UrlHighlightExtension($urlHighlight));
    $environment->addFilter(new TwigFilter('json_decode', function ($string) {
        return json_decode($string);
    }));
    $environment->addFilter(new TwigFilter('humanize', function ($string) {
        return \trim(\strtolower((string) \preg_replace(['/([A-Z])/', \sprintf('/[%s\s]+/', '_')], ['_$1', ' '], $string)));
    }));
    return $twig;
});

$container->set(Translator::class, static function (Container $container) {
    $settings = $container->get('settings')['locale'];
    $translator = new Translator(
        $settings['locale'],
        new MessageFormatter(new IdentityTranslator()),
        $settings['cache']
    );
    $translator->addLoader('mo', new MoFileLoader());
    __($translator); // Set translator instance
    return $translator;
});

$container->set(TranslatorMiddleware::class, static function (Container $container) {
    $settings = $container->get('settings')['locale'];
    $localPath = $settings['path'];
    $translator = $container->get(Translator::class);
    return new TranslatorMiddleware($translator, $localPath);
});

$container->set('iso4217', function() {
    return new Alcohol\ISO4217();
});

$container->set('mailer', function (Container $c) {
    $smtp = $c->get('settings')['smtp'];
    $transport = (new \Swift_SmtpTransport($smtp['addr'], $smtp['port'], $smtp['encr']))
      ->setUsername($smtp['user']) 
      ->setPassword($smtp['pass'])
      ->setStreamOptions(array('ssl' => array('allow_self_signed' => true, 'verify_peer' => false)));
    $mailer = new \Swift_Mailer($transport);
    $mailLogger = new \Swift_Plugins_Loggers_ArrayLogger();
    $mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($mailLogger));
    return $mailer;
});


// *************************************************
// GLUED CLASSES ***********************************
// ************************************************* 

// Form-data validation helper (send validation results
// via session to the original form upon failure)
$container->set('validator', function (Container $c) {
   return new Glued\Core\Classes\Validation\Validator;
});

$container->set('auth', function (Container $c) {
    return new Auth($c->get('settings'), 
                    $c->get('db'), 
                    $c->get('logger'), 
                    $c->get('events'),
                    $c->get('enforcer'),
                    $c->get('fscache'),
                    $c->get('utils')
                );
});

$container->set('utils', function (Container $c) {
    return new Utils($c->get('db'), $c->get('settings'), $c->get('routecollector'));
});

$container->set('stor', function (Container $c) {
    return new Stor($c->get('db'));
});

$container->set('crypto', function () {
    // TODO cleanup codebase from Crypto initialization
    return new Glued\Core\Classes\Crypto\Crypto();
});

$container->set('reqfactory', function () {
    return Psr17FactoryDiscovery::findUriFactory();
});

$container->set('urifactory', function () {
    return Psr17FactoryDiscovery::findRequestFactory();
});

$container->set('guzzle', function () {
    return new Guzzle();
});


// TODO 
// - classes/users.php
// - sjednotit namespace, ted mam app jako glued/core
//   v users.php bylo glued/core/classes ...
// - pouzit v accountscontrolleru na vypis 1 uzivatele
// - je na to preduelany twig, asi nehotovy accounts.twig
//   do ktereho v accountscontroleru passujeme obsah $users