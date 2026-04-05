<?php
// ============================================================
//  update_002_announcements.php
//  Adds announcements system with linked events/pottery.
//  ⚠️  DELETE THIS FILE after running it ⚠️
// ============================================================

define('UPDATE_TOKEN', 'update2024');

$token = $_GET['token'] ?? '';
$run   = isset($_POST['run']) && $token === UPDATE_TOKEN;

define('ROOT_PATH', dirname(__DIR__));

if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

if (class_exists('Dotenv\\Dotenv') && file_exists(ROOT_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
    $dotenv->safeLoad();
}

require_once __DIR__ . '/../config/config.php';

$results  = [];
$hasError = false;

$steps = [

    'Create announcements table' => "
        CREATE TABLE IF NOT EXISTS announcements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            publish_date DATETIME NOT NULL,
            image_path TEXT,
            image_thumb TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            KEY idx_publish_date (publish_date),
            KEY idx_created_at (created_at),
            FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'Create announcement_links table' => "
        CREATE TABLE IF NOT EXISTS announcement_links (
            id INT AUTO_INCREMENT PRIMARY KEY,
            announcement_id INT NOT NULL,
            entity_type ENUM('event', 'pottery') NOT NULL,
            entity_id INT NOT NULL,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
            KEY idx_entity_lookup (entity_type, entity_id),
            KEY idx_announcement_id (announcement_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'Create announcement_social_posts table' => "
        CREATE TABLE IF NOT EXISTS announcement_social_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            announcement_id INT NOT NULL,
            platform ENUM('instagram', 'tiktok') NOT NULL,
            posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            platform_post_id VARCHAR(255),
            status ENUM('success', 'pending', 'failed') DEFAULT 'pending',
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
            KEY idx_platform (platform),
            KEY idx_posted_at (posted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

];

if ($run) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        foreach ($steps as $label => $sql) {
            try {
                $stmt = $pdo->exec(trim($sql));
                $affected = is_int($stmt) ? $stmt : 0;
                $results[] = ['ok' => true, 'label' => $label, 'rows' => $affected];
            } catch (PDOException $e) {
                $results[]  = ['ok' => false, 'label' => $label, 'error' => $e->getMessage()];
                $hasError   = true;
            }
        }

        // Repair partial migration: add missing announcements.description if needed.
        try {
            $repairColumns = [
                'description' => "ALTER TABLE announcements ADD COLUMN description TEXT AFTER title",
                'created_by' => "ALTER TABLE announcements ADD COLUMN created_by INT NULL AFTER image_thumb",
            ];

            foreach ($repairColumns as $col => $sql) {
                $checkCol = $pdo->prepare(
                    "SELECT COUNT(*) AS cnt
                     FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = 'announcements'
                       AND COLUMN_NAME = ?"
                );
                $checkCol->execute([$col]);
                $checkColRow = $checkCol->fetch(PDO::FETCH_ASSOC);

                if ((int)($checkColRow['cnt'] ?? 0) === 0) {
                    $pdo->exec($sql);
                    $results[] = ['ok' => true, 'label' => 'Repair announcements.' . $col . ' column', 'rows' => 0];
                }
            }
        } catch (PDOException $e) {
            $results[] = ['ok' => false, 'label' => 'Repair announcements columns', 'error' => $e->getMessage()];
            $hasError  = true;
        }

    } catch (PDOException $e) {
        $hasError  = true;
        $results[] = ['ok' => false, 'label' => 'Database connection', 'error' => $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update 002 — Announcements System</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #F8F6F0; color: #1E2430; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 32px rgba(30,36,48,.12); max-width: 600px; width: 100%; padding: 2.5rem; }
        h1 { font-size: 1.5rem; margin-bottom: .25rem; }
        .subtitle { color: #7A8090; font-size: .9rem; margin-bottom: 2rem; }
        .what { background: #ECEEF2; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; font-size: .88rem; line-height: 1.7; }
        .what ul { margin-left: 1.2rem; margin-top: .4rem; }
        .info-row { display: flex; gap: .5rem; margin-bottom: .4rem; font-size: .88rem; }
        .info-row .lbl { color: #7A8090; min-width: 80px; }
        .info-row code { font-size: .83rem; }
        hr { border: none; border-top: 1px solid #E8E4D8; margin: 1.5rem 0; }
        .btn { display: inline-block; padding: .7rem 2rem; background: #D4A820; color: #fff; border: none; border-radius: 50px; font-size: .95rem; font-weight: 700; cursor: pointer; transition: background .2s; font-family: inherit; }
        .btn:hover { background: #B08A10; }
        .result-list { margin-top: 1.5rem; display: flex; flex-direction: column; gap: .5rem; }
        .result-item { display: flex; gap: .75rem; align-items: flex-start; font-size: .87rem; padding: .5rem .75rem; border-radius: 6px; }
        .result-item.ok   { background: #edf7ee; color: #2d6a30; }
        .result-item.fail { background: #fdf0ef; color: #a33028; }
        .result-item .icon { flex-shrink: 0; }
        .result-item .err { font-family: monospace; font-size: .78rem; margin-top: .2rem; opacity: .8; }
        .result-item .rows { font-size: .75rem; opacity: .65; margin-left: auto; white-space: nowrap; }
        .success-box { background: #edf7ee; border: 1.5px solid #6A8F5B; border-radius: 8px; padding: 1.25rem; margin-top: 1.5rem; color: #2d6a30; }
        .success-box h2 { font-size: 1rem; margin-bottom: .3rem; }
        .delete-box { background: #fdf0ef; border: 2px solid #D4726A; border-radius: 8px; padding: 1rem 1.25rem; margin-top: 1rem; font-size: .88rem; color: #a33028; line-height: 1.6; }
        .bad-token { background: #fdf0ef; border: 1.5px solid #D4726A; border-radius: 8px; padding: 1rem 1.25rem; color: #a33028; font-size: .88rem; }
    </style>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon-512.png">
    <link rel="apple-touch-icon" href="/favicon-512.png">
</head>
<body>
<div class="card">

    <h1>📢 Update 002 — Announcements System</h1>
    <p class="subtitle">Adds announcements with linked events/pottery and social media tracking. Run once, then delete.</p>

    <?php if ($token !== UPDATE_TOKEN && !$run): ?>

        <div class="bad-token">
            <strong>Access denied.</strong> Pass the token in the URL to continue.<br>
            <code>https://programmingpotter.com/update_002_announcements.php?token=update2024</code>
        </div>

    <?php elseif (!$run): ?>

        <div class="what">
            <strong>This update will:</strong>
            <ul>
                <li>Create <code>announcements</code> table with title, description, publish_date, images</li>
                <li>Create <code>announcement_links</code> table for linking events and pottery pieces</li>
                <li>Create <code>announcement_social_posts</code> table for tracking social media posts</li>
            </ul>
            <br>Safe to run on a live database — uses <code>IF NOT EXISTS</code>.
        </div>

        <div class="info-row"><span class="lbl">Host</span><code><?= htmlspecialchars(DB_HOST) ?></code></div>
        <div class="info-row"><span class="lbl">Database</span><code><?= htmlspecialchars(DB_NAME) ?></code></div>
        <div class="info-row"><span class="lbl">User</span><code><?= htmlspecialchars(DB_USER) ?></code></div>

        <hr>

        <form method="POST" action="?token=<?= htmlspecialchars(UPDATE_TOKEN) ?>">
            <button type="submit" name="run" value="1" class="btn">Run Update</button>
        </form>

    <?php else: ?>

        <div class="result-list">
            <?php foreach ($results as $r): ?>
            <div class="result-item <?= $r['ok'] ? 'ok' : 'fail' ?>">
                <span class="icon"><?= $r['ok'] ? '✅' : '❌' ?></span>
                <div style="flex:1">
                    <?= htmlspecialchars($r['label']) ?>
                    <?php if (!empty($r['error'])): ?>
                    <div class="err"><?= htmlspecialchars($r['error']) ?></div>
                    <?php endif; ?>
                </div>
                <?php if (isset($r['rows'])): ?><span class="rows"><?= htmlspecialchars($r['rows']) ?> rows</span><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (!$hasError): ?>
        <div class="success-box">
            <h2>✅ All updates applied successfully!</h2>
            <p>You can now delete this file and begin using announcements.</p>
        </div>
        <?php endif; ?>

    <?php endif; ?>

</div>
</body>
</html>
