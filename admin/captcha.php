<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

Auth::startSession();

if (Auth::check()) {
    http_response_code(403);
    exit;
}

Captcha::outputImage();
