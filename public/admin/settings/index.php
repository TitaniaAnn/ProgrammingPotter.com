<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'site_name', 'tagline', 'bio', 'about_text',
        'hero_title', 'hero_subtitle', 'hero_image', 'shop_intro', 'contact_email', 'profile_photo'
    ];
    foreach ($fields as $key) {
        if (isset($_POST[$key])) {
            Database::query(
                "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                [$key, trim($_POST[$key])]
            );
        }
    }
    // Ensure hero upload dir exists
    $heroDir = UPLOAD_PATH . 'hero/';
    if (!is_dir($heroDir)) { mkdir($heroDir, 0755, true); }

    // Handle hero image upload
    if (!empty($_FILES['hero_image_file']['name']) && $_FILES['hero_image_file']['error'] === UPLOAD_ERR_OK) {
        try {
            $result = ImageUpload::upload($_FILES['hero_image_file'], 'hero');
            Database::query(
                "INSERT INTO settings (setting_key, setting_value) VALUES ('hero_image', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                [$result['path']]
            );
        } catch (RuntimeException $e) {
            flash('error', 'Image upload failed: ' . $e->getMessage());
            redirect(SITE_URL . '/admin/settings/index.php');
        }
    }
    // Handle profile photo upload
    if (!empty($_FILES['profile_photo_file']['name']) && $_FILES['profile_photo_file']['error'] === UPLOAD_ERR_OK) {
        $profileDir = UPLOAD_PATH . 'profile/';
        if (!is_dir($profileDir)) { mkdir($profileDir, 0755, true); }
        try {
            $result = ImageUpload::upload($_FILES['profile_photo_file'], 'profile');
            Database::query(
                "INSERT INTO settings (setting_key, setting_value) VALUES ('profile_photo', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                [$result['path']]
            );
        } catch (RuntimeException $e) {
            flash('error', 'Profile photo upload failed: ' . $e->getMessage());
            redirect(SITE_URL . '/admin/settings/index.php');
        }
    }
    flash('success', 'Settings saved!');
    redirect(SITE_URL . '/admin/settings/index.php');
}

// Load current settings
$s = [];
$rows = Database::fetchAll("SELECT setting_key, setting_value FROM settings");
foreach ($rows as $row) { $s[$row['setting_key']] = $row['setting_value']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Site Settings</h1>
        </div>

        <form method="POST" enctype="multipart/form-data" class="admin-form">
            <div class="admin-card">
                <h2>Branding</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" name="site_name" value="<?= e($s['site_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Tagline</label>
                        <input type="text" name="tagline" value="<?= e($s['tagline'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Contact Email</label>
                        <input type="email" name="contact_email" value="<?= e($s['contact_email'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <h2>Homepage Hero</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Hero Title</label>
                        <input type="text" name="hero_title" value="<?= e($s['hero_title'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Hero Subtitle</label>
                        <input type="text" name="hero_subtitle" value="<?= e($s['hero_subtitle'] ?? '') ?>">
                    </div>
                    <div class="form-group form-group--full">
                        <label>Hero Background Photo</label>
                        <?php if (!empty($s['hero_image'])): ?>
                        <div style="margin-bottom:.75rem;">
                            <img src="/uploads/<?= e($s['hero_image']) ?>" style="max-height:140px; border-radius:8px; border:1px solid var(--sand);">
                            <p style="font-size:.8rem; color:var(--fog); margin-top:.3rem;">Current hero photo — upload a new one to replace it</p>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="hero_image_file" accept="image/*">
                        <p class="form-hint">Recommended: landscape photo, at least 1600×900px. The photo will have a sage overlay applied on top.</p>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <h2>About</h2>
                <div class="form-grid">
                    <div class="form-group form-group--full">
                        <label>Bio (About page — supports line breaks)</label>
                        <textarea name="bio" rows="6"><?= e($s['bio'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group form-group--full">
                        <label>About Strip Text (Homepage)</label>
                        <textarea name="about_text" rows="3"><?= e($s['about_text'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group form-group--full">
                        <label>Profile Photo (About page)</label>
                        <?php if (!empty($s['profile_photo'])): ?>
                        <div style="margin-bottom:.75rem; display:flex; align-items:center; gap:1rem;">
                            <img src="/uploads/<?= e($s['profile_photo']) ?>" style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid var(--clay);">
                            <p style="font-size:.8rem; color:var(--fog);">Current profile photo — upload a new one to replace it</p>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="profile_photo_file" accept="image/*">
                        <p class="form-hint">Square crop works best. Min 400×400px recommended.</p>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <h2>Shop</h2>
                <div class="form-group">
                    <label>Shop Introduction Text</label>
                    <textarea name="shop_intro" rows="2"><?= e($s['shop_intro'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="admin-btn admin-btn--primary">Save Settings</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
