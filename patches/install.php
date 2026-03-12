<?php
// ============================================================
//  install.php — ONE-TIME DATABASE INSTALLER
//  ⚠️  DELETE THIS FILE after installation is complete ⚠️
// ============================================================

// ── Safety token ────────────────────────────────────────────
// Change this to any secret word before uploading, then pass
// it as ?token=yourword in the URL to run the install.
define('INSTALL_TOKEN', 'pottery2024');

$token = $_GET['token'] ?? '';
$run   = isset($_POST['run']) && $token === INSTALL_TOKEN;

// Load config so we can read real DB credentials
require_once __DIR__ . '/../config/config.php';

$results  = [];
$hasError = false;

// ── SQL statements ───────────────────────────────────────────
$statements = [

    "Create admin_users table" => "
        CREATE TABLE IF NOT EXISTS admin_users (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            google_id   VARCHAR(255) UNIQUE NOT NULL,
            email       VARCHAR(255) UNIQUE NOT NULL,
            name        VARCHAR(255),
            avatar_url  TEXT,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    "Create pottery table" => "
        CREATE TABLE IF NOT EXISTS pottery (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            title       VARCHAR(255) NOT NULL,
            description TEXT,
            technique   VARCHAR(255),
            dimensions  VARCHAR(255),
            year        INT,
            image_path  TEXT NOT NULL,
            image_thumb TEXT,
            featured    TINYINT(1) DEFAULT 0,
            sort_order  INT DEFAULT 0,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    "Create shop_categories table" => "
        CREATE TABLE IF NOT EXISTS shop_categories (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(255) NOT NULL,
            slug        VARCHAR(255) UNIQUE NOT NULL,
            type        ENUM('pot','merch') NOT NULL,
            description TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    "Create products table" => "
        CREATE TABLE IF NOT EXISTS products (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            category_id     INT,
            name            VARCHAR(255) NOT NULL,
            description     TEXT,
            price           DECIMAL(10,2),
            type            ENUM('pot','merch') NOT NULL DEFAULT 'pot',
            status          ENUM('available','sold','coming_soon') DEFAULT 'available',
            image_path      TEXT,
            dimensions      VARCHAR(255),
            technique       VARCHAR(255),
            quantity        INT DEFAULT 1,
            pod_provider    ENUM('printful','printify','redbubble','other') NULL,
            pod_product_url TEXT NULL,
            pod_product_id  VARCHAR(255) NULL,
            external_url    TEXT NULL,
            sort_order      INT DEFAULT 0,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES shop_categories(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    "Create social_links table" => "
        CREATE TABLE IF NOT EXISTS social_links (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            platform    VARCHAR(50) NOT NULL,
            url         TEXT NOT NULL,
            handle      VARCHAR(255),
            active      TINYINT(1) DEFAULT 1,
            sort_order  INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    "Create social_posts table" => "
        CREATE TABLE IF NOT EXISTS social_posts (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            platform        VARCHAR(50) NOT NULL,
            post_id         VARCHAR(255),
            embed_code      TEXT,
            post_url        TEXT,
            caption         TEXT,
            thumbnail_url   TEXT,
            post_date       TIMESTAMP NULL,
            featured        TINYINT(1) DEFAULT 0,
            sort_order      INT DEFAULT 0,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    "Create settings table" => "
        CREATE TABLE IF NOT EXISTS settings (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            setting_key   VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    "Create orders table" => "
        CREATE TABLE IF NOT EXISTS orders (
            id                    INT AUTO_INCREMENT PRIMARY KEY,
            stripe_session_id     VARCHAR(255) UNIQUE,
            stripe_payment_intent VARCHAR(255),
            product_id            INT,
            product_name          VARCHAR(255),
            product_price         DECIMAL(10,2),
            quantity              INT DEFAULT 1,
            status                ENUM('pending','paid','shipped','cancelled','refunded') DEFAULT 'pending',
            customer_name         VARCHAR(255),
            customer_email        VARCHAR(255),
            shipping_line1        VARCHAR(255),
            shipping_line2        VARCHAR(255),
            shipping_city         VARCHAR(255),
            shipping_state        VARCHAR(255),
            shipping_postal_code  VARCHAR(255),
            shipping_country      VARCHAR(10),
            tracking_number       VARCHAR(255),
            tracking_carrier      VARCHAR(100),
            shipped_at            TIMESTAMP NULL,
            notes                 TEXT,
            created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    "Insert default settings" => "
        INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
        ('site_name',             'My Pottery'),
        ('tagline',               'Handcrafted ceramics made with love'),
        ('bio',                   'I am a ceramic artist. I create functional and sculptural pottery using traditional techniques.'),
        ('hero_title',            'Handcrafted Ceramics'),
        ('hero_subtitle',         'Each piece tells a story shaped by hand and fire'),
        ('about_text',            'Welcome to my pottery studio. I create functional and decorative ceramics using traditional wheel-throwing and hand-building techniques.'),
        ('contact_email',         ''),
        ('shop_intro',            'Own a piece of handcrafted art. Each pot is one-of-a-kind.'),
        ('shop_currency',         'USD')
    ",

    "Insert default shop categories" => "
        INSERT IGNORE INTO shop_categories (name, slug, type) VALUES
        ('Original Pots', 'original-pots', 'pot'),
        ('Mugs',          'mugs',          'pot'),
        ('Merch',         'merch',         'merch')
    ",
];

// ── Run install ──────────────────────────────────────────────
if ($run) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        foreach ($statements as $label => $sql) {
            try {
                $pdo->exec(trim($sql));
                $results[] = ['ok' => true, 'label' => $label];
            } catch (PDOException $e) {
                $results[]  = ['ok' => false, 'label' => $label, 'error' => $e->getMessage()];
                $hasError   = true;
            }
        }

        // Create upload directories
        $dirs = [
            UPLOAD_PATH,
            UPLOAD_PATH . 'pottery/',
            UPLOAD_PATH . 'products/',
            UPLOAD_PATH . 'hero/',
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true)
                    ? $results[] = ['ok' => true,  'label' => 'Create dir: ' . str_replace(UPLOAD_PATH, 'uploads/', $dir)]
                    : $results[] = ['ok' => false, 'label' => 'Create dir: ' . str_replace(UPLOAD_PATH, 'uploads/', $dir), 'error' => 'mkdir failed — check folder permissions'];
            } else {
                $results[] = ['ok' => true, 'label' => 'Dir already exists: ' . str_replace(UPLOAD_PATH, 'uploads/', $dir)];
            }
        }

    } catch (PDOException $e) {
        $hasError = true;
        $results[] = ['ok' => false, 'label' => 'Database connection', 'error' => $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install — Pottery Portfolio</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #FFF8EF; color: #3D2B1F; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 32px rgba(61,43,31,.12); max-width: 640px; width: 100%; padding: 2.5rem; }
        h1 { font-size: 1.6rem; margin-bottom: .35rem; color: #3D2B1F; }
        .subtitle { color: #8C7B72; font-size: .95rem; margin-bottom: 2rem; }
        .warning {
            background: #fff8e6; border: 1.5px solid #E8A838; border-radius: 8px;
            padding: 1rem 1.25rem; margin-bottom: 1.75rem; font-size: .9rem; line-height: 1.6;
        }
        .warning strong { color: #B07D10; }
        .info-row { display: flex; gap: .5rem; align-items: baseline; margin-bottom: .5rem; font-size: .9rem; }
        .info-row .label { color: #8C7B72; min-width: 80px; }
        .info-row .value { font-family: monospace; font-size: .88rem; color: #3D2B1F; }
        .divider { border: none; border-top: 1px solid #E2D5C3; margin: 1.5rem 0; }
        .btn {
            display: inline-block; padding: .75rem 2rem;
            background: #BF6B45; color: #fff; border: none;
            border-radius: 50px; font-size: 1rem; font-weight: 600;
            cursor: pointer; transition: background .2s;
        }
        .btn:hover { background: #9E5232; }
        .btn:disabled { background: #ccc; cursor: not-allowed; }
        .result-list { margin-top: 1.5rem; display: flex; flex-direction: column; gap: .5rem; }
        .result-item { display: flex; gap: .75rem; align-items: flex-start; font-size: .88rem; padding: .5rem .75rem; border-radius: 6px; }
        .result-item.ok    { background: #edf7ee; color: #2d6a30; }
        .result-item.fail  { background: #fdf0ef; color: #a33028; }
        .result-item .icon { font-size: 1rem; flex-shrink: 0; margin-top: .05rem; }
        .result-item .msg  { flex: 1; }
        .result-item .err  { font-family: monospace; font-size: .8rem; margin-top: .2rem; opacity: .8; }
        .success-banner {
            background: #edf7ee; border: 1.5px solid #6A8F5B; border-radius: 8px;
            padding: 1.25rem 1.5rem; margin-top: 1.5rem; color: #2d6a30;
        }
        .success-banner h2 { margin-bottom: .4rem; font-size: 1.1rem; }
        .delete-warning {
            background: #fdf0ef; border: 2px solid #D4726A; border-radius: 8px;
            padding: 1rem 1.25rem; margin-top: 1rem; font-size: .9rem; color: #a33028; line-height: 1.6;
        }
        .delete-warning strong { font-size: 1rem; }
        .bad-token { background: #fdf0ef; border: 1.5px solid #D4726A; border-radius: 8px; padding: 1rem 1.25rem; color: #a33028; font-size: .9rem; }
    </style>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon-512.png">
    <link rel="apple-touch-icon" href="/favicon-512.png">
</head>
<body>
<div class="card">

    <h1>🏺 Pottery Portfolio — Installer</h1>
    <p class="subtitle">One-time database setup. Delete this file when done.</p>

    <?php if ($token !== INSTALL_TOKEN && !$run): ?>

        <div class="bad-token">
            <strong>Access denied.</strong> Pass the correct install token in the URL to proceed.<br>
            Example: <code>https://yourdomain.com/install.php?token=pottery2024</code>
        </div>

    <?php elseif (!$run): ?>

        <div class="warning">
            <strong>⚠️ Before you run this:</strong><br>
            Make sure <code>config/config.php</code> has your correct database credentials. Tables are created with <code>IF NOT EXISTS</code> so it's safe to run on a fresh or existing database.
        </div>

        <div class="info-row"><span class="label">Host</span><span class="value"><?= htmlspecialchars(DB_HOST) ?></span></div>
        <div class="info-row"><span class="label">Database</span><span class="value"><?= htmlspecialchars(DB_NAME) ?></span></div>
        <div class="info-row"><span class="label">User</span><span class="value"><?= htmlspecialchars(DB_USER) ?></span></div>

        <hr class="divider">

        <p style="font-size:.9rem; color:#5C4033; margin-bottom:1.25rem;">This will create all tables and insert default settings. Existing data is never overwritten.</p>

        <form method="POST" action="?token=<?= htmlspecialchars(INSTALL_TOKEN) ?>">
            <button type="submit" name="run" value="1" class="btn">Run Installation</button>
        </form>

    <?php else: ?>

        <div class="result-list">
            <?php foreach ($results as $r): ?>
            <div class="result-item <?= $r['ok'] ? 'ok' : 'fail' ?>">
                <span class="icon"><?= $r['ok'] ? '✅' : '❌' ?></span>
                <div class="msg">
                    <?= htmlspecialchars($r['label']) ?>
                    <?php if (!$r['ok'] && !empty($r['error'])): ?>
                    <div class="err"><?= htmlspecialchars($r['error']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (!$hasError): ?>
        <div class="success-banner">
            <h2>✅ Installation complete!</h2>
            Next steps: add your GitHub OAuth credentials and Stripe keys to <code>config/config.php</code>, then log into <code>/admin/login.php</code>.
        </div>
        <?php endif; ?>

        <div class="delete-warning">
            <strong>🗑️ Delete this file now.</strong><br>
            Remove <code>public/install.php</code> from your server immediately. Leaving it up is a security risk.
        </div>

    <?php endif; ?>

</div>
</body>
</html>
