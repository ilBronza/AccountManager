<?php

// The shared workspace vendor can be selected without modifying it.
require getenv('ACCOUNTMANAGER_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';

$cache = sys_get_temp_dir() . '/accountmanager-tests-' . bin2hex(random_bytes(8));
mkdir($cache . '/views', 0700, true);
$environment = ['VIEW_COMPILED_PATH' => $cache . '/views', 'LOG_CHANNEL' => 'stderr'];
foreach (['SERVICES', 'PACKAGES', 'CONFIG', 'ROUTES', 'EVENTS'] as $type) {
    $environment['APP_' . $type . '_CACHE'] = $cache . '/' . strtolower($type) . '.php';
}
foreach ($environment as $name => $value) {
    putenv($name . '=' . $value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}
