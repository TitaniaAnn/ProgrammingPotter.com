<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/MigrationRunner.php';
Auth::requireLogin();
csrf_verify();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/admin/migrations/index.php');
}

$action  = $_POST['action']  ?? '';
$version = $_POST['version'] ?? '';

$runner = new MigrationRunner();
$user   = Auth::getUser();
$adminId = $user['id'] ?? null;

try {
    switch ($action) {
        case 'run':
            $result = $runner->apply($version, $adminId);
            $okCount = count(array_filter($result['statements'], fn($s) => $s['ok']));
            flash('success', "Migration $version applied — $okCount statement(s) executed.");
            break;

        case 'mark':
            $runner->markApplied($version, $adminId, 'Marked as already applied via admin UI.');
            flash('success', "Migration $version recorded as already applied.");
            break;

        case 'unmark':
            $runner->unmark($version);
            flash('success', "Migration $version removed from the ledger — it will reappear in pending.");
            break;

        default:
            flash('error', 'Unknown action.');
    }
} catch (\Throwable $e) {
    flash('error', $e->getMessage());
}

redirect(SITE_URL . '/admin/migrations/index.php');
