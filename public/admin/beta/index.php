<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$stats = [
    'total'    => Database::fetchOne("SELECT COUNT(*) n FROM beta_users")['n'] ?? 0,
    'android'  => Database::fetchOne("SELECT COUNT(*) n FROM beta_users WHERE platform IN ('android','both')")['n'] ?? 0,
    'ios'      => Database::fetchOne("SELECT COUNT(*) n FROM beta_users WHERE platform IN ('ios','both')")['n'] ?? 0,
    'bugs'     => Database::fetchOne("SELECT COUNT(*) n FROM beta_feedback WHERE type='bug'")['n'] ?? 0,
    'features' => Database::fetchOne("SELECT COUNT(*) n FROM beta_feedback WHERE type='feature'")['n'] ?? 0,
    'open'     => Database::fetchOne("SELECT COUNT(*) n FROM beta_feedback WHERE status='open'")['n'] ?? 0,
];
$recent_testers  = Database::fetchAll("SELECT * FROM beta_users ORDER BY created_at DESC LIMIT 5");
$recent_feedback = Database::fetchAll("SELECT f.*, u.name as user_name FROM beta_feedback f JOIN beta_users u ON u.id=f.user_id ORDER BY f.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beta Overview — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Beta App Overview</h1>
            <p>Summary of beta testers and feedback.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card__num"><?= $stats['total'] ?></div>
                <div class="stat-card__label">Total Testers</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__num"><?= $stats['android'] ?></div>
                <div class="stat-card__label">Android Testers</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__num"><?= $stats['ios'] ?></div>
                <div class="stat-card__label">iOS Testers</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__num"><?= $stats['bugs'] ?></div>
                <div class="stat-card__label">Bug Reports</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__num"><?= $stats['features'] ?></div>
                <div class="stat-card__label">Feature Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__num"><?= $stats['open'] ?></div>
                <div class="stat-card__label">Open Items</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-top:2rem">

            <div>
                <div class="admin-page-header" style="margin-bottom:.75rem">
                    <h2 style="font-size:1.1rem">Recent Testers</h2>
                    <a href="/admin/beta/testers.php" class="btn btn--sm">View all</a>
                </div>
                <table class="admin-table">
                    <thead><tr><th>Name</th><th>Platform</th><th>Joined</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent_testers as $t): ?>
                    <tr>
                        <td><?= e($t['name']) ?><br><small style="color:var(--ash)"><?= e($t['email']) ?></small></td>
                        <td><span class="badge"><?= e($t['platform']) ?></span></td>
                        <td><?= date('M j', strtotime($t['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$recent_testers): ?><tr><td colspan="3" style="text-align:center;color:var(--ash)">No testers yet</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div>
                <div class="admin-page-header" style="margin-bottom:.75rem">
                    <h2 style="font-size:1.1rem">Recent Feedback</h2>
                    <a href="/admin/beta/feedback.php" class="btn btn--sm">View all</a>
                </div>
                <table class="admin-table">
                    <thead><tr><th>Title</th><th>Type</th><th>From</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent_feedback as $f): ?>
                    <tr>
                        <td><?= e(mb_strimwidth($f['title'], 0, 40, '…')) ?></td>
                        <td><span class="badge badge--<?= $f['type'] === 'bug' ? 'danger' : 'info' ?>"><?= e($f['type']) ?></span></td>
                        <td><?= e($f['user_name']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$recent_feedback): ?><tr><td colspan="3" style="text-align:center;color:var(--ash)">No feedback yet</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</main>
</body>
</html>
