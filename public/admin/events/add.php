<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$isEdit       = !empty($_GET['id']);
$eventId      = $isEdit ? (int) $_GET['id'] : 0;
$event        = null;
$assignedIds  = [];

if ($isEdit) {
    $event = Database::fetchOne("SELECT * FROM events WHERE id = ?", [$eventId]);
    if (!$event) {
        flash('error', 'Event not found.');
        redirect(SITE_URL . '/admin/events/index.php');
    }
    $assignedRows = Database::fetchAll(
        "SELECT pottery_id FROM event_pottery WHERE event_id = ?", [$eventId]
    );
    $assignedIds = array_column($assignedRows, 'pottery_id');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        // Event type is locked after creation: read from the existing record on edit.
        $eventType = $isEdit ? $event['event_type'] : trim($_POST['event_type'] ?? '');
        $name      = trim($_POST['name'] ?? '');

        if (empty($eventType)) throw new RuntimeException('Event type is required.');
        if (empty($name))      throw new RuntimeException('Event name is required.');

        $data = [
            'event_type'   => $eventType,
            'name'         => $name,
            'description'  => trim($_POST['description'] ?? ''),
            'location'     => trim($_POST['location'] ?? ''),
            'url'          => trim($_POST['url'] ?? ''),
            'start_date'   => !empty($_POST['start_date'])   ? $_POST['start_date']   : null,
            'end_date'     => !empty($_POST['end_date'])     ? $_POST['end_date']     : null,
            'publish_date' => !empty($_POST['publish_date']) ? $_POST['publish_date'] : ($event['publish_date'] ?? date('Y-m-d')),
            'featured'     => isset($_POST['featured']) ? 1 : 0,
            'sort_order'   => (int)($_POST['sort_order'] ?? 0),
        ];

        if (in_array($eventType, ['pottery_sale', 'storefront_sale'], true)) {
            $data['daily_open_times'] = trim($_POST['daily_open_times'] ?? '');
        } else {
            $data['daily_open_times'] = null;
        }

        if ($eventType === 'class') {
            $classType = trim($_POST['class_type'] ?? '');
            if (empty($classType)) throw new RuntimeException('Class type is required for class events.');
            $data['class_type']       = $classType;
            $data['class_age_range']  = trim($_POST['class_age_range'] ?? '');
            $data['class_date_start'] = !empty($_POST['class_date_start']) ? $_POST['class_date_start'] : null;
            $data['class_date_end']   = !empty($_POST['class_date_end'])   ? $_POST['class_date_end']   : null;
            $data['class_time_start'] = !empty($_POST['class_time_start']) ? $_POST['class_time_start'] : null;
            $data['class_time_end']   = !empty($_POST['class_time_end'])   ? $_POST['class_time_end']   : null;
        } else {
            $data['class_type']       = null;
            $data['class_age_range']  = null;
            $data['class_date_start'] = null;
            $data['class_date_end']   = null;
            $data['class_time_start'] = null;
            $data['class_time_end']   = null;
        }

        if ($isEdit) {
            Database::update('events', $data, 'id = :id', ['id' => $eventId]);
            $finalId = $eventId;
            Database::delete('event_pottery', 'event_id = ?', [$finalId]);
        } else {
            $finalId = Database::insert('events', $data);
        }

        $selectedPieces = $_POST['poetry_ids'] ?? [];
        foreach ($selectedPieces as $pieceId) {
            $pieceId = (int) $pieceId;
            if ($pieceId > 0) {
                Database::insert('event_pottery', [
                    'event_id'   => $finalId,
                    'pottery_id' => $pieceId,
                ]);
            }
        }

        flash('success', $isEdit ? 'Event updated!' : 'Event created successfully!');
        redirect(SITE_URL . '/admin/events/add.php?id=' . $finalId);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$allPieces = Database::fetchAll(
    "SELECT id, title, image_thumb, image_path FROM pottery ORDER BY featured DESC, sort_order ASC"
);

$formData = $_POST + ($event ?? []);
$selectedPostedIds = $_POST['poetry_ids'] ?? null;
$activeAssignedIds = $selectedPostedIds !== null ? array_map('intval', $selectedPostedIds) : $assignedIds;

function getEventTypeLabel(string $type): string {
    $labels = [
        'pottery_show' => 'Pottery Show',
        'pottery_sale' => 'Pottery Sale',
        'storefront_sale' => 'Storefront Sale',
        'class' => 'Class',
    ];
    return $labels[$type] ?? $type;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit Event' : 'Add Event' ?> — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/css/admin.css">
    <style>
        .form-group.form-group--half { width: calc(50% - .375rem); display: inline-block; }
        .form-group.form-group--half:nth-child(even) { margin-left: .75rem; }
        @media (max-width: 768px) {
            .form-group.form-group--half { width: 100%; margin-left: 0 !important; display: block; }
        }
        .type-specific { display: none; padding: 1rem; background: var(--cream); border-left: 3px solid var(--clay); margin-top: 1rem; border-radius: 4px; }
        .type-specific.active { display: block; }
        .piece-checklist { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .piece-item { position: relative; border: 2px solid var(--cream-dk); border-radius: 8px; overflow: hidden; cursor: pointer; transition: border-color .2s; }
        .piece-item input[type="checkbox"] { display: none; }
        .piece-item input[type="checkbox"]:checked + .piece-item__content { border-color: var(--clay); box-shadow: inset 0 0 0 2px var(--clay); }
        .piece-item__content { padding: .375rem; }
        .piece-item__img { width: 100%; height: 100px; object-fit: cover; border-radius: 4px; }
        .piece-item__title { font-size: .75rem; font-weight: 600; margin-top: .25rem; color: var(--ink); word-break: break-word; }
        .piece-item__check { position: absolute; top: 4px; right: 4px; width: 20px; height: 20px; background: var(--clay); border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .9rem; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1><?= $isEdit ? 'Edit: ' . e($event['name']) : 'Add Event' ?></h1>
            <a href="/admin/events/index.php" class="admin-btn">← Back</a>
        </div>
        <?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

        <form method="POST" class="admin-form" id="eventForm">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="form-group form-group--full">
                    <?php if ($isEdit): ?>
                        <label>Event Type</label>
                        <div style="padding:.75rem;background:var(--cream);border-radius:4px;border:1px solid var(--cream-dk);">
                            <?= e(getEventTypeLabel($event['event_type'])) ?>
                        </div>
                        <small style="color:var(--ash);display:block;margin-top:.25rem;">(Cannot change event type after creation)</small>
                    <?php else: ?>
                        <label>Event Type *</label>
                        <select name="event_type" id="eventType" required>
                            <option value="">Select type...</option>
                            <option value="pottery_show"    <?= ($formData['event_type'] ?? '') === 'pottery_show' ? 'selected' : '' ?>>Pottery Show</option>
                            <option value="pottery_sale"    <?= ($formData['event_type'] ?? '') === 'pottery_sale' ? 'selected' : '' ?>>Pottery Sale</option>
                            <option value="storefront_sale" <?= ($formData['event_type'] ?? '') === 'storefront_sale' ? 'selected' : '' ?>>Storefront Sale</option>
                            <option value="class"           <?= ($formData['event_type'] ?? '') === 'class' ? 'selected' : '' ?>>Class</option>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="form-group form-group--full">
                    <label>Event Name *</label>
                    <input type="text" name="name" required value="<?= e($formData['name'] ?? '') ?>" placeholder="e.g. Winter Pottery Showcase">
                </div>

                <div class="form-group form-group--full">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Describe the event..."><?= e($formData['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group form-group--half">
                    <label>Location</label>
                    <input type="text" name="location" value="<?= e($formData['location'] ?? '') ?>" placeholder="e.g. Gladstone Studio">
                </div>

                <div class="form-group form-group--half">
                    <label>Event Website URL</label>
                    <input type="url" name="url" value="<?= e($formData['url'] ?? '') ?>" placeholder="https://...">
                </div>

                <div class="form-group form-group--half">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?= e($formData['start_date'] ?? '') ?>">
                </div>

                <div class="form-group form-group--half">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?= e($formData['end_date'] ?? '') ?>">
                </div>

                <div class="form-group form-group--full">
                    <label>Publish Date <small style="font-weight:400;color:var(--ash)">Event and piece assignments won't be visible until this date</small></label>
                    <input type="date" name="publish_date" value="<?= e($formData['publish_date'] ?? date('Y-m-d')) ?>">
                </div>

                <?php $eventType = $formData['event_type'] ?? ''; ?>
                <div id="salesFields" class="type-specific form-group form-group--full <?= in_array($eventType, ['pottery_sale', 'storefront_sale'], true) ? 'active' : '' ?>">
                    <label>Daily Open Times</label>
                    <textarea name="daily_open_times" rows="2" placeholder="e.g. 10am-5pm Daily"><?= e($formData['daily_open_times'] ?? '') ?></textarea>
                </div>

                <div id="classFields" class="type-specific form-group form-group--full <?= $eventType === 'class' ? 'active' : '' ?>">
                    <div class="form-group form-group--half">
                        <label>Class Type *</label>
                        <select name="class_type">
                            <option value="">Select...</option>
                            <option value="handbuilding"  <?= ($formData['class_type'] ?? '') === 'handbuilding'  ? 'selected' : '' ?>>Handbuilding</option>
                            <option value="wheelthrowing" <?= ($formData['class_type'] ?? '') === 'wheelthrowing' ? 'selected' : '' ?>>Wheel Throwing</option>
                            <option value="month_long"    <?= ($formData['class_type'] ?? '') === 'month_long'    ? 'selected' : '' ?>>Month Long</option>
                            <option value="workshop"      <?= ($formData['class_type'] ?? '') === 'workshop'      ? 'selected' : '' ?>>Workshop</option>
                        </select>
                    </div>
                    <div class="form-group form-group--half">
                        <label>Age Range</label>
                        <input type="text" name="class_age_range" value="<?= e($formData['class_age_range'] ?? '') ?>" placeholder="e.g. 12-18">
                    </div>
                    <div class="form-group form-group--half">
                        <label>Class Start Date</label>
                        <input type="date" name="class_date_start" value="<?= e($formData['class_date_start'] ?? '') ?>">
                    </div>
                    <div class="form-group form-group--half">
                        <label>Class End Date</label>
                        <input type="date" name="class_date_end" value="<?= e($formData['class_date_end'] ?? '') ?>">
                    </div>
                    <div class="form-group form-group--half">
                        <label>Start Time</label>
                        <input type="time" name="class_time_start" value="<?= e($formData['class_time_start'] ?? '') ?>">
                    </div>
                    <div class="form-group form-group--half">
                        <label>End Time</label>
                        <input type="time" name="class_time_end" value="<?= e($formData['class_time_end'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group form-group--half">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" value="<?= e($formData['sort_order'] ?? '0') ?>">
                    <small>Lower numbers appear first</small>
                </div>

                <div class="form-group form-group--half">
                    <label class="checkbox-label">
                        <input type="checkbox" name="featured" value="1" <?= !empty($formData['featured']) ? 'checked' : '' ?>>
                        <span>Feature on homepage</span>
                    </label>
                </div>

                <div class="form-group form-group--full">
                    <label>Assign Pottery Pieces</label>
                    <small style="color:var(--ash);display:block;margin-bottom:.5rem;">Select which pieces are featured in this event</small>
                    <?php if (empty($allPieces)): ?>
                        <p style="color:var(--ash)"><em>No pottery pieces available yet. <a href="/admin/pottery/add.php">Add pieces first</a>.</em></p>
                    <?php else: ?>
                        <div class="piece-checklist">
                            <?php foreach ($allPieces as $piece): ?>
                            <label class="piece-item">
                                <input type="checkbox" name="poetry_ids[]" value="<?= $piece['id'] ?>" <?= in_array($piece['id'], $activeAssignedIds, false) ? 'checked' : '' ?>>
                                <div class="piece-item__content">
                                    <img src="/uploads/<?= e($piece['image_thumb'] ?? $piece['image_path']) ?>" alt="<?= e($piece['title']) ?>" class="piece-item__img">
                                    <div class="piece-item__title"><?= e($piece['title']) ?></div>
                                    <div class="piece-item__check" style="display:none;">✓</div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="admin-btn admin-btn--primary"><?= $isEdit ? 'Save Changes' : 'Create Event' ?></button>
                <a href="/admin/events/index.php" class="admin-btn">Cancel</a>
            </div>
        </form>
    </div>
</main>

<script>
const IS_EDIT = <?= $isEdit ? 'true' : 'false' ?>;
const salesFields = document.getElementById('salesFields');
const classFields = document.getElementById('classFields');

function applyEventType(type) {
    salesFields.classList.toggle('active', ['pottery_sale', 'storefront_sale'].includes(type));
    classFields.classList.toggle('active', type === 'class');
}

if (!IS_EDIT) {
    const eventTypeSelect = document.getElementById('eventType');
    eventTypeSelect.addEventListener('change', () => applyEventType(eventTypeSelect.value));
    applyEventType(eventTypeSelect.value);
}

document.querySelectorAll('.piece-item input[type="checkbox"]').forEach(checkbox => {
    const check = checkbox.parentElement.querySelector('.piece-item__check');
    if (checkbox.checked) check.style.display = 'flex';
    checkbox.addEventListener('change', function() {
        check.style.display = this.checked ? 'flex' : 'none';
    });
});
</script>
</body>
</html>
