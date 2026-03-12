<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

// Support GET (from order list quick action) or POST (from order detail form)
$id = (int)(($_POST['id'] ?? $_GET['id'] ?? 0));
$order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$id]);

if (!$order) {
    flash('error', 'Order not found.');
    redirect(SITE_URL . '/admin/orders/index.php');
}

if ($order['status'] !== 'paid') {
    flash('error', 'Only paid orders can be marked as shipped.');
    redirect(SITE_URL . '/admin/orders/view.php?id=' . $id);
}

$trackingNumber  = trim($_POST['tracking_number'] ?? '');
$trackingCarrier = trim($_POST['tracking_carrier'] ?? '');
$notes           = trim($_POST['notes'] ?? '');

Database::update('orders', [
    'status'           => 'shipped',
    'tracking_number'  => $trackingNumber ?: null,
    'tracking_carrier' => $trackingCarrier ?: null,
    'shipped_at'       => date('Y-m-d H:i:s'),
    'notes'            => $notes ?: null,
], 'id = :id', ['id' => $id]);

// Re-fetch with updated data for email
$order = Database::fetchOne("SELECT * FROM orders WHERE id = ?", [$id]);
Mailer::notifyShipped($order);

flash('success', 'Order marked as shipped' . ($order['customer_email'] ? ' and customer notified.' : '.'));
redirect(SITE_URL . '/admin/orders/view.php?id=' . $id);
