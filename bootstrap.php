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

if (class_exists('Database')) {
    Database::migrate();
}
