<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'title'       => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'technique'   => trim($_POST['technique'] ?? ''),
            'dimensions'  => trim($_POST['dimensions'] ?? ''),
            'year'        => (int)($_POST['year'] ?? 0) ?: null,
            'featured'    => isset($_POST['featured']) ? 1 : 0,
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
        ];

        if (empty($data['title'])) throw new RuntimeException('Title is required.');

        $hasImage = false;
        foreach ($_FILES['images']['name'] ?? [] as $name) {
            if (!empty($name)) { $hasImage = true; break; }
        }
        if (!$hasImage) throw new RuntimeException('At least one image is required.');

        $data['image_path'] = '';
        $id = Database::insert('pottery', $data);

        $uploadedImages = [];
        $files = $_FILES['images'];
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK || empty($files['name'][$i])) continue;
            $file = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
            $upload = ImageUpload::upload($file, 'pottery');
            $uploadedImages[] = $upload;
        }

        if (empty($uploadedImages)) throw new RuntimeException('Image upload failed.');

        foreach ($uploadedImages as $idx => $upload) {
            Database::query(
                "INSERT INTO pottery_images (pottery_id, image_path, image_thumb, sort_order, is_primary) VALUES (?,?,?,?,?)",
                [$id, $upload['path'], $upload['thumb'], $idx, $idx === 0 ? 1 : 0]
            );
        }

        Database::query(
            "UPDATE pottery SET image_path = ?, image_thumb = ? WHERE id = ?",
            [$uploadedImages[0]['path'], $uploadedImages[0]['thumb'], $id]
        );

        flash('success', 'Piece added successfully!');
        redirect(SITE_URL . '/admin/pottery/index.php');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Piece — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/css/admin.css">
    <style>
        .img-preview-grid { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1rem; }
        .img-preview-item { position: relative; width: 120px; height: 120px; border-radius: 6px; overflow: hidden; border: 2px solid var(--cream-dk); }
        .img-preview-item img { width: 100%; height: 100%; object-fit: cover; }
        .img-preview-item .primary-badge { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(212,168,32,.9); color: #fff; font-size: .6rem; font-weight: 700; text-align: center; padding: .2rem; letter-spacing: .06em; text-transform: uppercase; }
        .img-preview-item .remove-btn { position: absolute; top: 4px; right: 4px; background: rgba(0,0,0,.65); color: #fff; border: none; width: 22px; height: 22px; border-radius: 50%; font-size: .85rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .file-add-more { width: 120px; height: 120px; border: 2px dashed var(--cream-dk); border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: .3rem; cursor: pointer; color: var(--ash); font-size: .75rem; text-align: center; transition: border-color .2s, color .2s; }
        .file-add-more:hover { border-color: var(--clay); color: var(--clay); }
        .file-add-more svg { width: 24px; height: 24px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Add Pottery Piece</h1>
            <a href="/admin/pottery/index.php" class="admin-btn">← Back</a>
        </div>
        <?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="admin-form" id="pieceForm">
            <div class="form-grid">
                <div class="form-group form-group--full">
                    <label>Title *</label>
                    <input type="text" name="title" required value="<?= e($_POST['title'] ?? '') ?>" placeholder="e.g. Speckled Stoneware Mug">
                </div>
                <div class="form-group form-group--full">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Describe this piece..."><?= e($_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Technique</label>
                    <input type="text" name="technique" value="<?= e($_POST['technique'] ?? '') ?>" placeholder="e.g. Wheel-thrown">
                </div>
                <div class="form-group">
                    <label>Dimensions</label>
                    <input type="text" name="dimensions" value="<?= e($_POST['dimensions'] ?? '') ?>" placeholder="e.g. 10cm H × 8cm W">
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <input type="number" name="year" value="<?= e($_POST['year'] ?? date('Y')) ?>" min="1900" max="<?= date('Y') ?>">
                </div>
                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" value="<?= e($_POST['sort_order'] ?? '0') ?>">
                    <small>Lower numbers appear first</small>
                </div>

                <div class="form-group form-group--full">
                    <label>Photos * <small style="font-weight:400;color:var(--ash)">First image is the cover. Up to 10.</small></label>
                    <div id="fileInputContainer"></div>
                    <div class="img-preview-grid" id="previewGrid">
                        <div class="file-add-more" id="addMoreBtn" onclick="document.getElementById('imgPicker').click()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add photos
                        </div>
                    </div>
                    <input type="file" id="imgPicker" accept="image/*" multiple style="display:none">
                    <small style="color:var(--ash);margin-top:.4rem;display:block">JPG, PNG, WebP — max 10MB each</small>
                </div>

                <div class="form-group form-group--full">
                    <label class="checkbox-label">
                        <input type="checkbox" name="featured" value="1" <?= !empty($_POST['featured']) ? 'checked' : '' ?>>
                        <span>Feature on homepage</span>
                    </label>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="admin-btn admin-btn--primary">Add Piece</button>
                <a href="/admin/pottery/index.php" class="admin-btn">Cancel</a>
            </div>
        </form>
    </div>
</main>
<script>
const allFiles = [];
const picker   = document.getElementById('imgPicker');
const grid     = document.getElementById('previewGrid');
const addBtn   = document.getElementById('addMoreBtn');
const MAX      = 10;

picker.addEventListener('change', () => {
    Array.from(picker.files).forEach(f => {
        if (allFiles.length >= MAX) return;
        allFiles.push(f);
        renderPreview(f, allFiles.length - 1);
    });
    picker.value = '';
    syncHiddenInput();
});

function renderPreview(file, idx) {
    const reader = new FileReader();
    reader.onload = e => {
        const div = document.createElement('div');
        div.className = 'img-preview-item';
        div.dataset.idx = idx;
        div.innerHTML = `<img src="${e.target.result}">
            ${idx === 0 ? '<div class="primary-badge">Cover</div>' : ''}
            <button type="button" class="remove-btn" onclick="removeImg(${idx})">×</button>`;
        grid.insertBefore(div, addBtn);
        if (allFiles.length >= MAX) addBtn.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function removeImg(idx) {
    allFiles.splice(idx, 1);
    document.querySelectorAll('.img-preview-item').forEach(el => el.remove());
    allFiles.forEach((f, i) => renderPreview(f, i));
    addBtn.style.display = allFiles.length >= MAX ? 'none' : 'flex';
    syncHiddenInput();
}

function syncHiddenInput() {
    document.getElementById('fileInputContainer').innerHTML = '';
    const dt = new DataTransfer();
    allFiles.forEach(f => dt.items.add(f));
    const inp = document.createElement('input');
    inp.type = 'file'; inp.name = 'images[]'; inp.multiple = true; inp.style.display = 'none';
    document.getElementById('fileInputContainer').appendChild(inp);
    try { inp.files = dt.files; } catch(e) {}
}

document.getElementById('pieceForm').addEventListener('submit', e => {
    if (allFiles.length === 0) { e.preventDefault(); alert('Please add at least one photo.'); return; }
    syncHiddenInput();
});
</script>
</body>
</html>
