<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

Auth::startSession();
Auth::logout();
Helpers::redirect('/admin/login.php');
