<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$id = (int)($_GET['id'] ?? 0);
$event = Database::fetchOne("SELECT * FROM events WHERE id = ?", [$id]);

if ($event) {
    Database::delete('events', 'id = ?', [$id]);
    // event_pottery entries are auto-deleted via CASCADE FK
    flash('success', 'Event deleted.');
} else {
    flash('error', 'Event not found.');
}

redirect(SITE_URL . '/admin/events/index.php');
