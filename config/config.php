<?php
// ============================================================
// config/config.php - Main configuration
// ============================================================

// Validate required environment variables up front so a missing .env produces
// a clear error rather than cryptic PDO/Stripe failures deeper in the stack.
$requiredEnv = [
    'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
    'GITHUB_CLIENT_ID', 'GITHUB_CLIENT_SECRET',
    'STRIPE_PUBLISHABLE_KEY', 'STRIPE_SECRET_KEY', 'STRIPE_WEBHOOK_SECRET',
];
$missing = [];
foreach ($requiredEnv as $var) {
    if (empty($_ENV[$var])) {
        $missing[] = $var;
    }
}
if (!empty($missing)) {
    http_response_code(500);
    error_log('Configuration error — missing .env values: ' . implode(', ', $missing));
    exit('Configuration error: site is not fully configured. See server logs.');
}

// Reject obvious placeholder Stripe values shipping into prod.
foreach (['STRIPE_PUBLISHABLE_KEY' => 'pk_', 'STRIPE_SECRET_KEY' => 'sk_', 'STRIPE_WEBHOOK_SECRET' => 'whsec_'] as $var => $expectedPrefix) {
    $val = $_ENV[$var];
    if (strpos($val, 'YOUR_') !== false || strpos($val, $expectedPrefix) !== 0) {
        http_response_code(500);
        error_log("Configuration error: {$var} looks like a placeholder.");
        exit('Configuration error: payment keys are not configured. See server logs.');
    }
}

define('DB_HOST', $_ENV['DB_HOST']);
define('DB_NAME', $_ENV['DB_NAME']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);

define('DB_CHARSET', 'utf8mb4');

define('SITE_URL', 'https://programmingpotter.com'); // No trailing slash
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// GitHub OAuth — get from https://github.com/settings/developers
// → OAuth Apps → New OAuth App
// → Authorization callback URL: https://yourdomain.com/admin/auth/callback.php
define('GITHUB_CLIENT_ID',     $_ENV['GITHUB_CLIENT_ID']);
define('GITHUB_CLIENT_SECRET', $_ENV['GITHUB_CLIENT_SECRET']);
define('GITHUB_REDIRECT_URI',  SITE_URL . '/admin/auth/callback.php');

// Comma-separated list of GitHub usernames allowed to log in as admin.
// Override via ALLOWED_GITHUB_USERS in .env without redeploying.
define('ALLOWED_GITHUB_USERS', $_ENV['ALLOWED_GITHUB_USERS'] ?? 'TitaniaAnn');

// Stripe — get from https://dashboard.stripe.com/apikeys
define('STRIPE_PUBLISHABLE_KEY', $_ENV['STRIPE_PUBLISHABLE_KEY']);
define('STRIPE_SECRET_KEY',      $_ENV['STRIPE_SECRET_KEY']);
define('STRIPE_WEBHOOK_SECRET',  $_ENV['STRIPE_WEBHOOK_SECRET']);

// Social Media Integration — for posting announcements
// Instagram Graph API — get from Meta Business Manager
// https://developers.facebook.com/docs/instagram-graph-api
define('INSTAGRAM_BUSINESS_ACCOUNT_ID', $_ENV['INSTAGRAM_BUSINESS_ACCOUNT_ID'] ?? '');
define('INSTAGRAM_ACCESS_TOKEN',        $_ENV['INSTAGRAM_ACCESS_TOKEN'] ?? '');

// TikTok Content Posting API — get from TikTok Developer Platform
// https://developers.tiktok.com/doc/content-posting-api
define('TIKTOK_BUSINESS_ACCOUNT_ID', $_ENV['TIKTOK_BUSINESS_ACCOUNT_ID'] ?? '');
define('TIKTOK_ACCESS_TOKEN',        $_ENV['TIKTOK_ACCESS_TOKEN'] ?? '');

define('SHOP_CURRENCY', 'usd');  // lowercase for Stripe API
define('SESSION_NAME', 'pottery_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// Image settings
define('MAX_IMAGE_SIZE', 10 * 1024 * 1024); // 10MB
define('THUMB_WIDTH', 600);
define('THUMB_HEIGHT', 600);
