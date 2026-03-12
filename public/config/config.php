<?php
// ============================================================
// config/config.php - Main configuration
// ============================================================

define('DB_HOST', $_ENV['DB_HOST']);
define('DB_NAME', $_ENV['DB_NAME']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);

define('SITE_URL', 'https://programmingpotter.com'); // No trailing slash
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// GitHub OAuth — get from https://github.com/settings/developers
// → OAuth Apps → New OAuth App
// → Authorization callback URL: https://yourdomain.com/admin/auth/callback.php
define('GITHUB_CLIENT_ID',     'Ov23lirCPHrpwWDyNrWe');
define('GITHUB_CLIENT_SECRET', '4134b636437c668b8318c17e56d4a4987b623cd0');
define('GITHUB_REDIRECT_URI',  SITE_URL . '/admin/auth/callback.php');

// Your GitHub username — only this account can log in as admin
define('ALLOWED_GITHUB_USERS', 'TitaniaAnn');

// Stripe — get from https://dashboard.stripe.com/apikeys
// Use test keys (pk_test_..., sk_test_...) until ready to go live
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_PUBLISHABLE_KEY');
define('STRIPE_SECRET_KEY',      'sk_test_YOUR_SECRET_KEY');
define('STRIPE_WEBHOOK_SECRET',  'whsec_YOUR_WEBHOOK_SECRET');

// Stripe will install via: composer require stripe/stripe-php
// OR drop stripe-php into /includes/stripe-php/ (manual install)

define('SHOP_CURRENCY', 'usd');  // lowercase for Stripe API
define('SESSION_NAME', 'pottery_session');
define('SESSION_LIFETIME', 86400 * 7); // 7 days

// Image settings
define('MAX_IMAGE_SIZE', 10 * 1024 * 1024); // 10MB
define('THUMB_WIDTH', 600);
define('THUMB_HEIGHT', 600);
