<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $eventType = trim($_POST['event_type'] ?? '');
        $name = trim($_POST['name'] ?? '');
        
        if (empty($eventType)) throw new RuntimeException('Event type is required.');
        if (empty($name)) throw new RuntimeException('Event name is required.');

        // Build common data
        $data = [
            'event_type'  => $eventType,
            'name'        => $name,
            'description' => trim($_POST['description'] ?? ''),
            'location'    => trim($_POST['location'] ?? ''),
            'url'         => trim($_POST['url'] ?? ''),
            'start_date'  => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'end_date'    => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'publish_date' => !empty($_POST['publish_date']) ? $_POST['publish_date'] : null,
            'featured'    => isset($_POST['featured']) ? 1 : 0,
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
        ];

        // Type-specific fields: Sales
        if (in_array($eventType, ['pottery_sale', 'storefront_sale'])) {
            $data['daily_open_times'] = trim($_POST['daily_open_times'] ?? '');
        }

        // Type-specific fields: Classes
        if ($eventType === 'class') {
            $classType = trim($_POST['class_type'] ?? '');
            if (empty($classType)) throw new RuntimeException('Class type is required for class events.');
            
            $data['class_type']       = $classType;
            $data['class_age_range']  = trim($_POST['class_age_range'] ?? '');
            $data['class_date_start'] = !empty($_POST['class_date_start']) ? $_POST['class_date_start'] : null;
            $data['class_date_end']   = !empty($_POST['class_date_end']) ? $_POST['class_date_end'] : null;
            $data['class_time_start'] = !empty($_POST['class_time_start']) ? $_POST['class_time_start'] : null;
            $data['class_time_end']   = !empty($_POST['class_time_end']) ? $_POST['class_time_end'] : null;
        }

        // Insert event
        $eventId = Database::insert('events', $data);

        // Handle piece assignments
        $selectedPieces = $_POST['poetry_ids'] ?? [];
        if (!empty($selectedPieces)) {
            foreach ($selectedPieces as $pieceId) {
                $pieceId = (int)$pieceId;
                if ($pieceId > 0) {
                    Database::insert('event_pottery', [
                        'event_id' => $eventId,
                        'pottery_id' => $pieceId,
                    ]);
                }
            }
        }

        flash('success', 'Event created successfully!');
        redirect(SITE_URL . '/admin/events/index.php');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Load all pottery for assignment checklist
$allPieces = Database::fetchAll(
    "SELECT id, title, image_thumb, image_path FROM pottery ORDER BY featured DESC, sort_order ASC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event — Admin</title>
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
            <h1>Add Event</h1>
            <a href="/admin/events/index.php" class="admin-btn">← Back</a>
        </div>
        <?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

        <form method="POST" class="admin-form" id="eventForm">
            <div class="form-grid">
                <!-- Event type -->
                <div class="form-group form-group--full">
                    <label>Event Type *</label>
                    <select name="event_type" id="eventType" required>
                        <option value="">Select type...</option>
                        <option value="pottery_show" <?= $_POST['event_type'] === 'pottery_show' ? 'selected' : '' ?>>Pottery Show</option>
                        <option value="pottery_sale" <?= $_POST['event_type'] === 'pottery_sale' ? 'selected' : '' ?>>Pottery Sale</option>
                        <option value="storefront_sale" <?= $_POST['event_type'] === 'storefront_sale' ? 'selected' : '' ?>>Storefront Sale</option>
                        <option value="class" <?= $_POST['event_type'] === 'class' ? 'selected' : '' ?>>Class</option>
                    </select>
                </div>

                <!-- Name -->
                <div class="form-group form-group--full">
                    <label>Event Name *</label>
                    <input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>" placeholder="e.g. Winter Pottery Showcase">
                </div>

                <!-- Description -->
                <div class="form-group form-group--full">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Describe the event..."><?= e($_POST['description'] ?? '') ?></textarea>
                </div>

                <!-- Location -->
                <div class="form-group form-group--half">
                    <label>Location</label>
                    <input type="text" name="location" value="<?= e($_POST['location'] ?? '') ?>" placeholder="e.g. Gladstone Studio">
                </div>

                <!-- URL -->
                <div class="form-group form-group--half">
                    <label>Event Website URL</label>
                    <input type="url" name="url" value="<?= e($_POST['url'] ?? '') ?>" placeholder="https://...">
                </div>

                <!-- Start Date -->
                <div class="form-group form-group--half">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?= e($_POST['start_date'] ?? '') ?>">
                </div>

                <!-- End Date -->
                <div class="form-group form-group--half">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?= e($_POST['end_date'] ?? '') ?>">
                </div>

                <!-- Publish Date -->
                <div class="form-group form-group--full">
                    <label>Publish Date <small style="font-weight:400;color:var(--ash)">Event and piece assignments won't be visible until this date</small></label>
                    <input type="date" name="publish_date" value="<?= e($_POST['publish_date'] ?? '') ?>">
                </div>

                <!-- TYPE-SPECIFIC: SALES -->
                <div id="salesFields" class="type-specific form-group form-group--full">
                    <label>Daily Open Times</label>
                    <textarea name="daily_open_times" rows="2" placeholder="e.g. 10am-5pm Daily"><?= e($_POST['daily_open_times'] ?? '') ?></textarea>
                </div>

                <!-- TYPE-SPECIFIC: CLASSES -->
                <div id="classFields" class="type-specific form-group form-group--full">
                    <div class="form-group form-group--half">
                        <label>Class Type *</label>
                        <select name="class_type">
                            <option value="">Select...</option>
                            <option value="handbuilding" <?= ($_POST['class_type'] ?? '') === 'handbuilding' ? 'selected' : '' ?>>Handbuilding</option>
                            <option value="wheelthrowing" <?= ($_POST['class_type'] ?? '') === 'wheelthrowing' ? 'selected' : '' ?>>Wheel Throwing</option>
                            <option value="month_long" <?= ($_POST['class_type'] ?? '') === 'month_long' ? 'selected' : '' ?>>Month Long</option>
                            <option value="workshop" <?= ($_POST['class_type'] ?? '') === 'workshop' ? 'selected' : '' ?>>Workshop</option>
                        </select>
                    </div>
                    <div class="form-group form-group--half">
                        <label>Age Range</label>
                        <input type="text" name="class_age_range" value="<?= e($_POST['class_age_range'] ?? '') ?>" placeholder="e.g. 12-18">
                    </div>
                    <div class="form-group form-group--half">
                        <label>Class Start Date</label>
                        <input type="date" name="class_date_start" value="<?= e($_POST['class_date_start'] ?? '') ?>">
                    </div>
                    <div class="form-group form-group--half">
                        <label>Class End Date</label>
                        <input type="date" name="class_date_end" value="<?= e($_POST['class_date_end'] ?? '') ?>">
                    </div>
                    <div class="form-group form-group--half">
                        <label>Start Time</label>
                        <input type="time" name="class_time_start" value="<?= e($_POST['class_time_start'] ?? '') ?>">
                    </div>
                    <div class="form-group form-group--half">
                        <label>End Time</label>
                        <input type="time" name="class_time_end" value="<?= e($_POST['class_time_end'] ?? '') ?>">
                    </div>
                </div>

                <!-- Sort Order -->
                <div class="form-group form-group--half">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" value="<?= e($_POST['sort_order'] ?? '0') ?>">
                    <small>Lower numbers appear first</small>
                </div>

                <!-- Featured -->
                <div class="form-group form-group--half">
                    <label class="checkbox-label">
                        <input type="checkbox" name="featured" value="1" <?= !empty($_POST['featured']) ? 'checked' : '' ?>>
                        <span>Feature on homepage</span>
                    </label>
                </div>

                <!-- Pottery Piece Assignment -->
                <div class="form-group form-group--full">
                    <label>Assign Pottery Pieces</label>
                    <small style="color:var(--ash);display:block;margin-bottom:.5rem;">Select which pieces are featured in this event</small>
                    <?php if (empty($allPieces)): ?>
                        <p style="color:var(--ash)"><em>No pottery pieces available yet. <a href="/admin/pottery/add.php">Add pieces first</a>.</em></p>
                    <?php else: ?>
                        <div class="piece-checklist">
                            <?php foreach ($allPieces as $piece): ?>
                            <label class="piece-item">
                                <input type="checkbox" name="poetry_ids[]" value="<?= $piece['id'] ?>" <?= !empty($_POST['poetry_ids']) && in_array($piece['id'], $_POST['poetry_ids']) ? 'checked' : '' ?>>
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
                <button type="submit" class="admin-btn admin-btn--primary">Create Event</button>
                <a href="/admin/events/index.php" class="admin-btn">Cancel</a>
            </div>
        </form>
    </div>
</main>

<script>
// Show/hide type-specific fields
const eventTypeSelect = document.getElementById('eventType');
const salesFields = document.getElementById('salesFields');
const classFields = document.getElementById('classFields');

function updateTypeFields() {
    const type = eventTypeSelect.value;
    salesFields.classList.toggle('active', ['pottery_sale', 'storefront_sale'].includes(type));
    classFields.classList.toggle('active', type === 'class');
}

eventTypeSelect.addEventListener('change', updateTypeFields);
updateTypeFields(); // On page load

// Checkbox styling
document.querySelectorAll('.piece-item input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const check = this.parentElement.querySelector('.piece-item__check');
        if (this.checked) {
            check.style.display = 'flex';
        } else {
            check.style.display = 'none';
        }
    });
    // Initialize on page load
    if (checkbox.checked) {
        checkbox.parentElement.querySelector('.piece-item__check').style.display = 'flex';
    }
});
</script>
</body>
</html>
