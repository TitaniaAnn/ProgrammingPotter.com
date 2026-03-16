<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($id && in_array($status, ['open','in_progress','closed'])) {
        Database::update('beta_feedback', ['status' => $status], 'id = :id', ['id' => $id]);
    }
    redirect('/admin/beta/feedback.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
}

$type   = $_GET['type'] ?? 'all';
$status = $_GET['status'] ?? 'all';

$params = [];
$conditions = [];
if ($type !== 'all')   { $conditions[] = 'f.type = ?';   $params[] = $type; }
if ($status !== 'all') { $conditions[] = 'f.status = ?'; $params[] = $status; }
$whereSQL = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$items = Database::fetchAll(
    "SELECT f.*, u.name as user_name, u.email as user_email
     FROM beta_feedback f
     JOIN beta_users u ON u.id = f.user_id
     $whereSQL
     ORDER BY f.created_at DESC",
    $params
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beta Feedback — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .feedback-body { background:var(--cream); border:1px solid var(--cream-dk); border-radius:4px; padding:.75rem 1rem; font-size:.88rem; color:var(--bark-lt); white-space:pre-wrap; margin-top:.4rem; }
        .feedback-row td { vertical-align:top; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Beta Feedback</h1>
        </div>

        <!-- Filters -->
        <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;align-items:center">
            <div style="display:flex;gap:.4rem">
                <?php foreach (['all' => 'All Types', 'bug' => 'Bugs', 'feature' => 'Features'] as $val => $label): ?>
                <a href="?type=<?= $val ?>&status=<?= urlencode($status) ?>"
                   class="btn btn--sm <?= $type === $val ? 'btn--primary' : '' ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>
            <div style="display:flex;gap:.4rem">
                <?php foreach (['all' => 'All Status', 'open' => 'Open', 'in_progress' => 'In Progress', 'closed' => 'Closed'] as $val => $label): ?>
                <a href="?type=<?= urlencode($type) ?>&status=<?= $val ?>"
                   class="btn btn--sm <?= $status === $val ? 'btn--primary' : '' ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <table class="admin-table">
            <thead>
                <tr><th>Type</th><th>Title &amp; Description</th><th>Submitted By</th><th>GitHub</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
            <tr class="feedback-row">
                <td>
                    <span class="badge badge--<?= $item['type'] === 'bug' ? 'danger' : 'info' ?>">
                        <?= e($item['type']) ?>
                    </span>
                </td>
                <td>
                    <strong><?= e($item['title']) ?></strong>
                    <div class="feedback-body"><?= e($item['body']) ?></div>
                </td>
                <td>
                    <?= e($item['user_name']) ?><br>
                    <small style="color:var(--ash)"><?= e($item['user_email']) ?></small>
                </td>
                <td>
                    <?php if ($item['github_issue_url']): ?>
                        <a href="<?= e($item['github_issue_url']) ?>" target="_blank" rel="noopener">#<?= $item['github_issue_number'] ?> ↗</a>
                    <?php else: ?>
                        <span style="color:var(--ash)">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                        <select name="status" onchange="this.form.submit()" class="admin-select" style="font-size:.82rem;padding:.3rem .5rem">
                            <?php foreach (['open','in_progress','closed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $item['status'] === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td><?= date('M j, Y', strtotime($item['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--ash);padding:2rem">No feedback found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
