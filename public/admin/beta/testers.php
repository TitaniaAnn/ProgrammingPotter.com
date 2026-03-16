<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

// Handle approve/revoke/delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id) {
        if ($action === 'approve') {
            Database::update('beta_users', ['approved' => 1], 'id = :id', ['id' => $id]);
            flash('success', 'Tester approved.');
        } elseif ($action === 'revoke') {
            Database::update('beta_users', ['approved' => 0], 'id = :id', ['id' => $id]);
            flash('success', 'Access revoked.');
        } elseif ($action === 'delete') {
            Database::delete('beta_users', 'id = :id', ['id' => $id]);
            flash('success', 'Tester deleted.');
        }
    }
    redirect('/admin/beta/testers.php');
}

$platform = $_GET['platform'] ?? 'all';
$where    = match($platform) {
    'android' => "WHERE platform IN ('android','both')",
    'ios'     => "WHERE platform IN ('ios','both')",
    default   => '',
};
$testers = Database::fetchAll("SELECT * FROM beta_users $where ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beta Testers — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Beta Testers</h1>
            <a href="/admin/beta/email.php" class="btn btn--primary">Email Testers</a>
        </div>

        <div style="display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap">
            <?php foreach (['all' => 'All', 'android' => 'Android', 'ios' => 'iOS'] as $val => $label): ?>
            <a href="?platform=<?= $val ?>"
               class="btn btn--sm <?= $platform === $val ? 'btn--primary' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Platform</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($testers as $t): ?>
            <tr>
                <td><?= e($t['name']) ?></td>
                <td><?= e($t['email']) ?></td>
                <td><span class="badge"><?= e($t['platform']) ?></span></td>
                <td>
                    <?php if ($t['approved']): ?>
                        <span class="badge badge--success">Active</span>
                    <?php else: ?>
                        <span class="badge badge--danger">Revoked</span>
                    <?php endif; ?>
                </td>
                <td><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
                <td><?= $t['last_login'] ? date('M j, Y', strtotime($t['last_login'])) : '—' ?></td>
                <td>
                    <form method="POST" style="display:inline-flex;gap:.4rem">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <?php if ($t['approved']): ?>
                            <button name="action" value="revoke" class="btn btn--sm btn--warning">Revoke</button>
                        <?php else: ?>
                            <button name="action" value="approve" class="btn btn--sm btn--success">Approve</button>
                        <?php endif; ?>
                        <button name="action" value="delete" class="btn btn--sm btn--danger"
                                onclick="return confirm('Delete this tester and all their feedback?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$testers): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--ash);padding:2rem">No testers found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
