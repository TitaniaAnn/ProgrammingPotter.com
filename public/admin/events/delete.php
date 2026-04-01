<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::requireLogin();

$message = '';
$error = '';

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$event = Database::fetchOne("SELECT * FROM events WHERE id = ?", [$id]);
if (!$event) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Database::delete('events', "id = ?", [$id]);
        $message = 'Event deleted successfully!';
        header('Refresh: 2; url=index.php');
    } catch (Exception $e) {
        $error = 'Failed to delete event: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Event — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include __DIR__ . '/../partials/topbar.php'; ?>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Delete Event</h1>
            <a href="index.php" class="btn btn--outline">
                <i class="fas fa-arrow-left"></i> Back to Events
            </a>
        </div>

        <?php if ($message): ?>
        <div class="alert alert--success">
            <i class="fas fa-check-circle"></i>
            <?= e($message) ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert--error">
            <i class="fas fa-exclamation-circle"></i>
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <div class="delete-confirmation">
            <div class="delete-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Are you sure you want to delete this event?</h3>
                <p>This action cannot be undone. The event and all associated data will be permanently removed.</p>
            </div>

            <div class="event-summary">
                <h4>Event Details:</h4>
                <div class="event-summary__content">
                    <div class="event-summary__row">
                        <strong>Title:</strong> <?= e($event['title']) ?>
                    </div>
                    <div class="event-summary__row">
                        <strong>Type:</strong> <?= ucfirst($event['type']) ?>
                    </div>
                    <div class="event-summary__row">
                        <strong>Date:</strong> <?= date('M j, Y', strtotime($event['start_date'])) ?>
                        <?php if ($event['end_date'] && $event['end_date'] !== $event['start_date']): ?>
                            - <?= date('M j, Y', strtotime($event['end_date'])) ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($event['location']): ?>
                    <div class="event-summary__row">
                        <strong>Location:</strong> <?= e($event['location']) ?>
                    </div>
                    <?php endif; ?>
                    <div class="event-summary__row">
                        <strong>Status:</strong> <?= ucfirst($event['status']) ?>
                    </div>
                </div>
            </div>

            <form method="POST" class="delete-form">
                <div class="delete-actions">
                    <button type="submit" class="btn btn--danger">
                        <i class="fas fa-trash"></i> Yes, Delete Event
                    </button>
                    <a href="index.php" class="btn btn--outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<script src="/admin/js/admin.js"></script>
</body>
</html>