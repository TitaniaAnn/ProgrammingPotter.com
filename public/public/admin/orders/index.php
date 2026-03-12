<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$statusFilter = $_GET['status'] ?? '';
$params = [];
$where  = '';
if ($statusFilter) {
    $where = 'WHERE status = ?';
    $params[] = $statusFilter;
}

$orders = Database::fetchAll(
    "SELECT * FROM orders $where ORDER BY created_at DESC",
    $params
);

$counts = Database::fetchAll(
    "SELECT status, COUNT(*) as n FROM orders GROUP BY status"
);
$countMap = array_column($counts, 'n', 'status');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Orders</h1>
        </div>

        <!-- Status filter tabs -->
        <div class="order-tabs">
            <a href="/admin/orders/index.php" class="order-tab <?= !$statusFilter ? 'active' : '' ?>">
                All <span class="badge"><?= array_sum($countMap) ?></span>
            </a>
            <?php foreach (['paid' => '💳', 'shipped' => '📦', 'pending' => '⏳', 'cancelled' => '✗', 'refunded' => '↩'] as $s => $icon): ?>
            <?php if (!empty($countMap[$s])): ?>
            <a href="/admin/orders/index.php?status=<?= $s ?>"
               class="order-tab order-tab--<?= $s ?> <?= $statusFilter === $s ? 'active' : '' ?>">
                <?= $icon ?> <?= ucfirst($s) ?> <span class="badge"><?= $countMap[$s] ?></span>
            </a>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?php if (empty($orders)): ?>
        <div class="empty-admin">
            <p style="font-size:2rem;">🏺</p>
            <p>No orders yet<?= $statusFilter ? " with status \"$statusFilter\"" : '' ?>.</p>
        </div>
        <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><span class="order-id">#<?= $o['id'] ?></span></td>
                        <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                        <td>
                            <strong><?= e($o['product_name']) ?></strong>
                            <?php if ($o['quantity'] > 1): ?><small>×<?= $o['quantity'] ?></small><?php endif; ?>
                        </td>
                        <td>
                            <?php if ($o['customer_name']): ?>
                            <div><?= e($o['customer_name']) ?></div>
                            <small><?= e($o['customer_email'] ?? '') ?></small>
                            <?php else: ?>
                            <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>$<?= number_format($o['product_price'] * $o['quantity'], 2) ?></td>
                        <td><span class="status-badge status-badge--<?= e($o['status']) ?>"><?= e($o['status']) ?></span></td>
                        <td>
                            <a href="/admin/orders/view.php?id=<?= $o['id'] ?>" class="admin-btn admin-btn--sm">View</a>
                            <?php if ($o['status'] === 'paid'): ?>
                            <a href="/admin/orders/ship.php?id=<?= $o['id'] ?>" class="admin-btn admin-btn--sm admin-btn--primary">Mark Shipped</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Revenue summary -->
        <?php
        $revenue = Database::fetchOne(
            "SELECT SUM(product_price * quantity) as total, COUNT(*) as n FROM orders WHERE status IN ('paid','shipped')"
        );
        ?>
        <div class="revenue-summary">
            <strong>Total revenue (paid + shipped):</strong>
            $<?= number_format($revenue['total'] ?? 0, 2) ?> across <?= $revenue['n'] ?> order<?= $revenue['n'] != 1 ? 's' : '' ?>
        </div>
        <?php endif; ?>
    </div>
</main>
<style>
.order-tabs { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.5rem; }
.order-tab { padding:.4rem 1rem; border:1px solid var(--sand); border-radius:var(--radius); font-size:.85rem; color:var(--soil); text-decoration:none; transition:all .2s; }
.order-tab.active, .order-tab:hover { background:var(--clay); color:#fff; border-color:var(--clay); }
.order-id { font-family:monospace; font-size:.85rem; color:var(--ash); }
.revenue-summary { margin-top:1.5rem; padding:1rem 1.25rem; background:#fff; border-left:3px solid var(--clay); font-size:.9rem; border-radius:0 var(--radius) var(--radius) 0; }
.status-badge--refunded { background:#f3e0ff; color:#6a1b9a; }
</style>
</body>
</html>
