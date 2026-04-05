<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

function tableExists(string $table): bool {
    $row = Database::fetchOne(
        "SELECT COUNT(*) AS cnt
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
        [$table]
    );
    return (int)($row['cnt'] ?? 0) > 0;
}

function columnExists(string $table, string $column): bool {
    $row = Database::fetchOne(
        "SELECT COUNT(*) AS cnt
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        [$table, $column]
    );
    return (int)($row['cnt'] ?? 0) > 0;
}

$checks = [
    [
        'type' => 'table',
        'label' => 'Announcements table exists',
        'table' => 'announcements',
        'fix_sql' => "CREATE TABLE IF NOT EXISTS announcements (\n    id INT AUTO_INCREMENT PRIMARY KEY,\n    title VARCHAR(255) NOT NULL,\n    description TEXT,\n    publish_date DATETIME NOT NULL,\n    image_path TEXT,\n    image_thumb TEXT,\n    created_by INT,\n    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n    KEY idx_publish_date (publish_date),\n    KEY idx_created_at (created_at),\n    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    ],
    [
        'type' => 'column',
        'label' => 'announcements.description column exists',
        'table' => 'announcements',
        'column' => 'description',
        'fix_sql' => "ALTER TABLE announcements ADD COLUMN description TEXT AFTER title;",
    ],
    [
        'type' => 'column',
        'label' => 'announcements.publish_date column exists',
        'table' => 'announcements',
        'column' => 'publish_date',
        'fix_sql' => "ALTER TABLE announcements ADD COLUMN publish_date DATETIME NOT NULL AFTER description;",
    ],
    [
        'type' => 'column',
        'label' => 'announcements.image_path column exists',
        'table' => 'announcements',
        'column' => 'image_path',
        'fix_sql' => "ALTER TABLE announcements ADD COLUMN image_path TEXT AFTER publish_date;",
    ],
    [
        'type' => 'column',
        'label' => 'announcements.image_thumb column exists',
        'table' => 'announcements',
        'column' => 'image_thumb',
        'fix_sql' => "ALTER TABLE announcements ADD COLUMN image_thumb TEXT AFTER image_path;",
    ],
    [
        'type' => 'column',
        'label' => 'announcements.created_by column exists',
        'table' => 'announcements',
        'column' => 'created_by',
        'fix_sql' => "ALTER TABLE announcements ADD COLUMN created_by INT NULL AFTER image_thumb;",
    ],
    [
        'type' => 'table',
        'label' => 'announcement_links table exists',
        'table' => 'announcement_links',
        'fix_sql' => "CREATE TABLE IF NOT EXISTS announcement_links (\n    id INT AUTO_INCREMENT PRIMARY KEY,\n    announcement_id INT NOT NULL,\n    entity_type ENUM('event', 'pottery') NOT NULL,\n    entity_id INT NOT NULL,\n    sort_order INT DEFAULT 0,\n    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,\n    KEY idx_entity_lookup (entity_type, entity_id),\n    KEY idx_announcement_id (announcement_id)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    ],
    [
        'type' => 'table',
        'label' => 'announcement_social_posts table exists',
        'table' => 'announcement_social_posts',
        'fix_sql' => "CREATE TABLE IF NOT EXISTS announcement_social_posts (\n    id INT AUTO_INCREMENT PRIMARY KEY,\n    announcement_id INT NOT NULL,\n    platform ENUM('instagram', 'tiktok') NOT NULL,\n    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n    platform_post_id VARCHAR(255),\n    status ENUM('success', 'pending', 'failed') DEFAULT 'pending',\n    error_message TEXT,\n    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,\n    KEY idx_platform (platform),\n    KEY idx_posted_at (posted_at)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    ],
    [
        'type' => 'column',
        'label' => 'events.publish_date column exists',
        'table' => 'events',
        'column' => 'publish_date',
        'fix_sql' => "ALTER TABLE events ADD COLUMN publish_date DATE AFTER end_date;",
    ],
    [
        'type' => 'table',
        'label' => 'pottery table exists',
        'table' => 'pottery',
        'fix_sql' => "CREATE TABLE IF NOT EXISTS pottery (\n    id INT AUTO_INCREMENT PRIMARY KEY,\n    title VARCHAR(255) NOT NULL,\n    description TEXT,\n    technique VARCHAR(255),\n    dimensions VARCHAR(255),\n    year INT,\n    image_path TEXT NOT NULL,\n    image_thumb TEXT,\n    featured TINYINT(1) DEFAULT 0,\n    sort_order INT DEFAULT 0,\n    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
    ],
];

$results = [];
$okCount = 0;

foreach ($checks as $check) {
    $ok = false;
    if ($check['type'] === 'table') {
        $ok = tableExists($check['table']);
    } elseif ($check['type'] === 'column') {
        $ok = tableExists($check['table']) && columnExists($check['table'], $check['column']);
    }

    if ($ok) {
        $okCount++;
    }

    $results[] = [
        'label' => $check['label'],
        'ok' => $ok,
        'fix_sql' => $check['fix_sql'],
    ];
}

$total = count($results);
$missingCount = $total - $okCount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schema Health — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .health-summary { display: flex; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
        .health-pill { background: #fff; border: 1px solid var(--cream-dk); border-radius: 999px; padding: .45rem .85rem; font-size: .82rem; }
        .health-pill--ok { color: #1b6f31; border-color: #b8deba; background: #edf7ee; }
        .health-pill--warn { color: #8d3b13; border-color: #f0c7b2; background: #fff1ea; }
        .health-list { display: grid; gap: .8rem; }
        .health-item { background: #fff; border: 1px solid var(--cream-dk); border-radius: var(--radius); padding: .9rem 1rem; }
        .health-item__head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .health-item__label { font-size: .9rem; color: var(--soil); }
        .status-dot { font-size: .78rem; text-transform: uppercase; letter-spacing: .06em; padding: .2rem .55rem; border-radius: 999px; }
        .status-dot--ok { background: #edf7ee; color: #1b6f31; border: 1px solid #b8deba; }
        .status-dot--bad { background: #fdf0ef; color: #8f2c24; border: 1px solid #edc0bd; }
        .fix-sql { margin-top: .65rem; }
        .fix-sql pre { background: #f7f8fb; border: 1px solid var(--cream-dk); border-radius: 4px; padding: .65rem; font-size: .78rem; overflow-x: auto; }
        .fix-actions { display: flex; align-items: center; gap: .5rem; margin-top: .45rem; }
        .copy-note { font-size: .75rem; color: var(--fog); }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Schema Health</h1>
            <a href="/admin/settings/index.php" class="admin-btn">Back to Settings</a>
        </div>

        <div class="health-summary">
            <span class="health-pill">Checks: <?= $total ?></span>
            <span class="health-pill health-pill--ok">Passing: <?= $okCount ?></span>
            <span class="health-pill <?= $missingCount > 0 ? 'health-pill--warn' : 'health-pill--ok' ?>">Missing: <?= $missingCount ?></span>
        </div>

        <?php if ($missingCount > 0): ?>
            <div class="alert alert--error">Some schema checks failed. Copy and run the SQL snippets below, or run the relevant patch file again.</div>
        <?php endif; ?>

        <div class="health-list">
            <?php foreach ($results as $i => $row): ?>
                <div class="health-item">
                    <div class="health-item__head">
                        <div class="health-item__label"><?= e($row['label']) ?></div>
                        <span class="status-dot <?= $row['ok'] ? 'status-dot--ok' : 'status-dot--bad' ?>"><?= $row['ok'] ? 'ok' : 'missing' ?></span>
                    </div>

                    <?php if (!$row['ok']): ?>
                        <div class="fix-sql">
                            <pre id="sql-<?= $i ?>"><?= e($row['fix_sql']) ?></pre>
                            <div class="fix-actions">
                                <button type="button" class="admin-btn admin-btn--sm" onclick="copySql('sql-<?= $i ?>', this)">Copy SQL</button>
                                <span class="copy-note">Run this in your DB tool (phpMyAdmin/MySQL client).</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<script>
function copySql(id, btn) {
    const text = document.getElementById(id)?.innerText || '';
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        const old = btn.textContent;
        btn.textContent = 'Copied';
        setTimeout(() => { btn.textContent = old; }, 1200);
    });
}
</script>
</body>
</html>
