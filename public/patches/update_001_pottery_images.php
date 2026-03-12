<?php
// ============================================================
//  update_001_pottery_images.php
//  Adds multiple-images support to pottery pieces.
//  ⚠️  DELETE THIS FILE after running it ⚠️
// ============================================================

define('UPDATE_TOKEN', 'update2024');

$token = $_GET['token'] ?? '';
$run   = isset($_POST['run']) && $token === UPDATE_TOKEN;

require_once __DIR__ . '/../config/config.php';

$results  = [];
$hasError = false;

$steps = [

    'Create pottery_images table' => "
        CREATE TABLE IF NOT EXISTS pottery_images (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            pottery_id  INT NOT NULL,
            image_path  TEXT NOT NULL,
            image_thumb TEXT,
            sort_order  INT DEFAULT 0,
            is_primary  TINYINT(1) DEFAULT 0,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (pottery_id) REFERENCES pottery(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",

    'Migrate existing primary images' => "
        INSERT INTO pottery_images (pottery_id, image_path, image_thumb, sort_order, is_primary)
        SELECT id, image_path, COALESCE(image_thumb, image_path), 0, 1
        FROM pottery
        WHERE image_path IS NOT NULL
          AND image_path != ''
          AND id NOT IN (SELECT DISTINCT pottery_id FROM pottery_images)
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
    <title>Update 001 — Pottery Images</title>
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

    <h1>🏺 Update 001 — Pottery Images</h1>
    <p class="subtitle">Adds multi-image support to pottery pieces. Run once, then delete.</p>

    <?php if ($token !== UPDATE_TOKEN && !$run): ?>

        <div class="bad-token">
            <strong>Access denied.</strong> Pass the token in the URL to continue.<br>
            <code>https://programmingpotter.com/update_001_pottery_images.php?token=update2024</code>
        </div>

    <?php elseif (!$run): ?>

        <div class="what">
            <strong>This update will:</strong>
            <ul>
                <li>Create a new <code>pottery_images</code> table</li>
                <li>Copy every existing piece's current image into it as the primary image</li>
            </ul>
            <br>Safe to run on a live database — uses <code>IF NOT EXISTS</code> and skips pieces already migrated.
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
                <?php if ($r['ok'] && isset($r['rows']) && $r['rows'] > 0): ?>
                <span class="rows"><?= $r['rows'] ?> row<?= $r['rows'] !== 1 ? 's' : '' ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (!$hasError): ?>
        <div class="success-box">
            <h2>✅ Update complete!</h2>
            You can now upload multiple photos per piece in the admin panel.
        </div>
        <?php endif; ?>

        <div class="delete-box">
            <strong>🗑️ Delete this file now.</strong><br>
            Remove <code>public/update_001_pottery_images.php</code> from your server.
        </div>

    <?php endif; ?>

</div>
</body>
</html>
