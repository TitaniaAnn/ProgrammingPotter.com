<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/Auth.php';

Auth::requireLogin();

$message = '';
$error = '';

// Get all pottery pieces for assignment
$potteryPieces = Database::fetchAll(
    "SELECT id, title, image_path, technique FROM pottery ORDER BY title ASC"
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = $_POST['type'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = trim($_POST['end_date'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $max_attendees = trim($_POST['max_attendees'] ?? '');
    $registration_required = isset($_POST['registration_required']) ? 1 : 0;
    $registration_url = trim($_POST['registration_url'] ?? '');
    $website_url = trim($_POST['website_url'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Validation
    if (empty($title) || empty($type) || empty($start_date)) {
        $error = 'Please fill in all required fields.';
    } elseif (!in_array($type, ['show', 'sale', 'class'])) {
        $error = 'Invalid event type.';
    } elseif (!in_array($status, ['draft', 'published', 'cancelled'])) {
        $error = 'Invalid status.';
    } else {
        try {
            $eventId = Database::insert('events', [
                'title' => $title,
                'description' => $description,
                'type' => $type,
                'status' => $status,
                'start_date' => $start_date,
                'end_date' => $end_date ?: null,
                'start_time' => $start_time ?: null,
                'end_time' => $end_time ?: null,
                'location' => $location,
                'address' => $address,
                'max_attendees' => $max_attendees ?: null,
                'registration_required' => $registration_required,
                'registration_url' => $registration_url,
                'website_url' => $website_url,
                'notes' => $notes
            ]);

            // Assign pieces to event if it's a show or sale
            if (in_array($type, ['show', 'sale']) && isset($_POST['assigned_pieces'])) {
                $assignedPieces = $_POST['assigned_pieces'];
                foreach ($assignedPieces as $potteryId) {
                    if (is_numeric($potteryId)) {
                        try {
                            Database::insert('event_pieces', [
                                'event_id' => $eventId,
                                'pottery_id' => $potteryId
                            ]);
                        } catch (Exception $e) {
                            // Ignore duplicate key errors (piece already assigned)
                            if (!str_contains($e->getMessage(), 'Duplicate entry')) {
                                throw $e;
                            }
                        }
                    }
                }
            }

            $message = 'Event created successfully!';
            // Redirect after 2 seconds
            header('Refresh: 2; url=index.php');
        } catch (Exception $e) {
            $error = 'Failed to create event: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<?php include __DIR__ . '/../partials/topbar.php'; ?>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Add Event</h1>
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

        <form method="POST" class="admin-form">
            <div class="form-section">
                <h3>Basic Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Event Title *</label>
                        <input type="text" id="title" name="title" required value="<?= e($_POST['title'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="type">Event Type *</label>
                        <select id="type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="show" <?= ($_POST['type'] ?? '') === 'show' ? 'selected' : '' ?>>Show/Exhibition</option>
                            <option value="sale" <?= ($_POST['type'] ?? '') === 'sale' ? 'selected' : '' ?>>Sale</option>
                            <option value="class" <?= ($_POST['type'] ?? '') === 'class' ? 'selected' : '' ?>>Class/Workshop</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"><?= e($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="draft" <?= ($_POST['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= ($_POST['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="cancelled" <?= ($_POST['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Date & Time</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date *</label>
                        <input type="date" id="start_date" name="start_date" required value="<?= e($_POST['start_date'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?= e($_POST['end_date'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" value="<?= e($_POST['start_time'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" value="<?= e($_POST['end_time'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Location</h3>
                <div class="form-group">
                    <label for="location">Location Name</label>
                    <input type="text" id="location" name="location" value="<?= e($_POST['location'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="address">Full Address</label>
                    <textarea id="address" name="address" rows="3"><?= e($_POST['address'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3>Registration & Links</h3>
                <div class="form-group">
                    <label for="max_attendees">Max Attendees</label>
                    <input type="number" id="max_attendees" name="max_attendees" min="1" value="<?= e($_POST['max_attendees'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="registration_required" <?= isset($_POST['registration_required']) ? 'checked' : '' ?>>
                        Registration Required
                    </label>
                </div>

                <div class="form-group">
                    <label for="registration_url">Registration URL</label>
                    <input type="url" id="registration_url" name="registration_url" value="<?= e($_POST['registration_url'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="website_url">Event Website URL</label>
                    <input type="url" id="website_url" name="website_url" value="<?= e($_POST['website_url'] ?? '') ?>">
                </div>
            </div>

            <div class="form-section">
                <h3>Additional Notes</h3>
                <div class="form-group">
                    <label for="notes">Internal Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Internal notes for admins only"><?= e($_POST['notes'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn--primary">
                    <i class="fas fa-save"></i> Create Event
                </button>
                <a href="index.php" class="btn btn--outline">Cancel</a>
            </div>
        </form>
    </div>
</main>

<script src="/admin/js/admin.js"></script>
</body>
</html>