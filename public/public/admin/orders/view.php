<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$id    = (int)($_GET['id'] ?? 0);
$order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$id]);
if (!$order) { flash('error', 'Order not found.'); redirect(SITE_URL . '/admin/orders/index.php'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $order['id'] ?> — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Order #<?= $order['id'] ?> <span class="status-badge status-badge--<?= e($order['status']) ?>"><?= e($order['status']) ?></span></h1>
            <a href="/admin/orders/index.php" class="admin-btn">← All Orders</a>
        </div>

        <div class="two-col-layout">
            <!-- Order details -->
            <div>
                <div class="admin-card">
                    <h2>Item</h2>
                    <p><strong><?= e($order['product_name']) ?></strong></p>
                    <p>Quantity: <?= $order['quantity'] ?></p>
                    <p>Price per item: $<?= number_format($order['product_price'], 2) ?></p>
                    <p><strong>Total: $<?= number_format($order['product_price'] * $order['quantity'], 2) ?></strong></p>
                    <p style="margin-top:.75rem; font-size:.82rem; color:var(--ash);">
                        Stripe session: <code><?= e($order['stripe_session_id'] ?? '—') ?></code>
                    </p>
                </div>

                <div class="admin-card">
                    <h2>Customer</h2>
                    <?php if ($order['customer_name']): ?>
                    <p><strong><?= e($order['customer_name']) ?></strong></p>
                    <p><a href="mailto:<?= e($order['customer_email']) ?>"><?= e($order['customer_email']) ?></a></p>
                    <?php else: ?>
                    <p class="muted">Customer details not yet available (payment may be pending).</p>
                    <?php endif; ?>
                </div>

                <div class="admin-card">
                    <h2>Shipping Address</h2>
                    <?php if ($order['shipping_line1']): ?>
                    <address style="font-style:normal; line-height:1.8;">
                        <?= e($order['customer_name']) ?><br>
                        <?= e($order['shipping_line1']) ?><br>
                        <?php if ($order['shipping_line2']): ?><?= e($order['shipping_line2']) ?><br><?php endif; ?>
                        <?= e($order['shipping_city']) ?>, <?= e($order['shipping_state']) ?> <?= e($order['shipping_postal_code']) ?><br>
                        <?= e($order['shipping_country']) ?>
                    </address>
                    <?php else: ?>
                    <p class="muted">No shipping address yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions -->
            <div>
                <?php if ($order['status'] === 'paid'): ?>
                <div class="admin-card">
                    <h2>Mark as Shipped</h2>
                    <form method="POST" action="/admin/orders/ship.php" class="admin-form">
                        <input type="hidden" name="id" value="<?= $order['id'] ?>">
                        <div class="form-group">
                            <label>Carrier</label>
                            <select name="tracking_carrier">
                                <option value="USPS">USPS</option>
                                <option value="UPS">UPS</option>
                                <option value="FedEx">FedEx</option>
                                <option value="DHL">DHL</option>
                                <option value="Royal Mail">Royal Mail</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tracking Number</label>
                            <input type="text" name="tracking_number" placeholder="1Z999AA10123456784">
                        </div>
                        <div class="form-group">
                            <label>Notes (internal)</label>
                            <textarea name="notes" rows="2" placeholder="Optional packing notes..."><?= e($order['notes'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="admin-btn admin-btn--primary">Mark Shipped & Email Customer</button>
                    </form>
                </div>
                <?php elseif ($order['status'] === 'shipped'): ?>
                <div class="admin-card">
                    <h2>Shipped ✓</h2>
                    <p>Carrier: <strong><?= e($order['tracking_carrier'] ?? '—') ?></strong></p>
                    <p>Tracking: <strong><?= e($order['tracking_number'] ?? '—') ?></strong></p>
                    <?php if ($order['shipped_at']): ?>
                    <p>Shipped: <?= date('d M Y H:i', strtotime($order['shipped_at'])) ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="admin-card">
                    <h2>Timeline</h2>
                    <div class="timeline">
                        <div class="timeline-item timeline-item--done">
                            <span>Order created</span>
                            <small><?= date('d M Y H:i', strtotime($order['created_at'])) ?></small>
                        </div>
                        <div class="timeline-item <?= in_array($order['status'], ['paid','shipped','refunded']) ? 'timeline-item--done' : '' ?>">
                            <span>Payment confirmed</span>
                        </div>
                        <div class="timeline-item <?= $order['status'] === 'shipped' ? 'timeline-item--done' : '' ?>">
                            <span>Shipped</span>
                            <?php if ($order['shipped_at']): ?>
                            <small><?= date('d M Y', strtotime($order['shipped_at'])) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($order['notes']): ?>
                <div class="admin-card">
                    <h2>Notes</h2>
                    <p><?= nl2br(e($order['notes'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<style>
.timeline { display:flex; flex-direction:column; gap:.75rem; }
.timeline-item { padding:.6rem .75rem; border-left:2px solid var(--sand); padding-left:1rem; font-size:.88rem; }
.timeline-item--done { border-left-color:var(--clay); }
.timeline-item span { display:block; font-weight:500; }
.timeline-item small { color:var(--ash); font-size:.78rem; }
</style>
</body>
</html>
