<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';
Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
    Helpers::redirect('/admin/pages/index.php');
}

LegalPagesContent::seedIntoDatabase(true);
Helpers::flash('success', 'Contenu légal injecté dans les pages vides.');
Helpers::redirect('/admin/pages/index.php');
