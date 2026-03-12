<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';

$code  = $_GET['code']  ?? '';
$state = $_GET['state'] ?? '';
$error = $_GET['error'] ?? '';

if ($error) {
    $_SESSION['login_error'] = 'GitHub sign-in was cancelled or failed.';
    redirect(SITE_URL . '/admin/login.php');
}

if (!$code || !$state) {
    $_SESSION['login_error'] = 'Invalid OAuth response.';
    redirect(SITE_URL . '/admin/login.php');
}

if (Auth::handleGitHubCallback($code, $state)) {
    redirect(SITE_URL . '/admin/dashboard.php');
} else {
    $_SESSION['login_error'] = 'Access denied. Your GitHub account is not authorised to access this admin.';
    redirect(SITE_URL . '/admin/login.php');
}
