<?php

declare(strict_types=1);

define('ROOT_PATH', __DIR__);

spl_autoload_register(function (string $class): void {
    $file = ROOT_PATH . '/lib/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

date_default_timezone_set('Africa/Casablanca');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');

if (class_exists('SecurityHeaders')) {
    SecurityHeaders::apply();
}

if (class_exists('Database')) {
    Database::migrate();
}
