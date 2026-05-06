<?php
// tests/bootstrap.php — minimal harness for unit-testing classes in includes/
// without booting the full app (no .env, no DB, no Stripe SDK).
//
// We hand-define the constants that the tested classes touch, and require()
// only the files under test. Anything that would pull in Database / Stripe /
// Mailer (which reach into the network or DB) is loaded lazily by the test
// itself, never here.

require __DIR__ . '/../vendor/autoload.php';

// Constants consumed by includes/ImageUpload.php.
if (!defined('MAX_IMAGE_SIZE')) {
    define('MAX_IMAGE_SIZE', 10 * 1024 * 1024);
}
if (!defined('THUMB_WIDTH'))  { define('THUMB_WIDTH', 600); }
if (!defined('THUMB_HEIGHT')) { define('THUMB_HEIGHT', 600); }
if (!defined('UPLOAD_PATH')) {
    // Tests never actually move a file through this path — move_uploaded_file
    // refuses non-real uploads — but the constant is referenced before that
    // check, so it must be defined.
    define('UPLOAD_PATH', sys_get_temp_dir() . DIRECTORY_SEPARATOR);
}
if (!defined('UPLOAD_URL')) { define('UPLOAD_URL', 'http://example.test/uploads/'); }

// Constants consumed by includes/Auth.php session params.
if (!defined('SESSION_NAME'))     { define('SESSION_NAME', 'pottery_test_session'); }
if (!defined('SESSION_LIFETIME')) { define('SESSION_LIFETIME', 3600); }
if (!defined('SITE_URL'))         { define('SITE_URL', 'http://example.test'); }

require_once __DIR__ . '/../includes/MultiFileUpload.php';
require_once __DIR__ . '/../includes/ImageUpload.php';
require_once __DIR__ . '/../includes/Mailer.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/MigrationRunner.php';
