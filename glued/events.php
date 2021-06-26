<?php

declare(strict_types=1);
$ev = $container->get('events');

$ev->on('core.auth.user.create', function($auth_id) use ($container) {
    $a = $container->get('auth');
    $p = $container->get('settings')['policies']['default'];
    $e = $container->get('enforcer');
    $m = $e->getModel();
    if ($auth_id == 1) {
        foreach ($p['p'] as $rule) { $a->safeAddPolicy($e, $m, 'p', 'p', $rule); }
        foreach ($p['g'] as $rule) { $a->safeAddPolicy($e, $m, 'g', 'g', $rule); }
        foreach ($p['g2'] as $rule) { $a->safeAddPolicy($e, $m, 'g', 'g2', $rule); }
    } else {
        $rule = [ (string)$auth_id, 'usage', '0' ];
        $a->safeAddPolicy($e, $m, 'g', 'g', $rule);
    }
});

$ev->on('core.install.migration.addrbac', function($auth_id) use ($container) {
    $a = $container->get('auth');
    $p = $container->get('settings')['policies']['default'];
    $e = $container->get('enforcer');
    $m = $e->getModel();

    $creds = $a->cred_list();
    foreach ($creds as $cred) {
        $auth_id = $cred['c_uid'];
        if ($auth_id == 1) {
            foreach ($p['p'] as $rule) { $a->safeAddPolicy($e, $m, 'p', 'p', $rule); }
            foreach ($p['g'] as $rule) { $a->safeAddPolicy($e, $m, 'g', 'g', $rule); }
            foreach ($p['g2'] as $rule) { $a->safeAddPolicy($e, $m, 'g', 'g2', $rule); }
        } else {
            $rule = [ (string)$auth_id, 'usage', '0' ];
            $a->safeAddPolicy($e, $m, 'g', 'g', $rule);
        }
    }
});
