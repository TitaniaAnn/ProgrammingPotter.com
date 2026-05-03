<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$isEdit         = !empty($_GET['id']);
$pieceId        = $isEdit ? (int) $_GET['id'] : 0;
$piece          = null;
$existingImages = [];

if ($isEdit) {
    $piece = Database::fetchOne("SELECT * FROM pottery WHERE id = ?", [$pieceId]);
    if (!$piece) {
        flash('error', 'Piece not found.');
        redirect(SITE_URL . '/admin/pottery/index.php');
    }
    $existingImages = Database::fetchAll(
        "SELECT * FROM pottery_images WHERE pottery_id = ? ORDER BY sort_order ASC, id ASC",
        [$pieceId]
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
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

        if (empty($data['title'])) {
            throw new RuntimeException('Title is required.');
        }

        $newUploads = [];
        foreach (MultiFileUpload::parse($_FILES['images'] ?? null) as $file) {
            $newUploads[] = ImageUpload::upload($file, 'pottery');
        }

        if (!$isEdit && empty($newUploads)) {
            throw new RuntimeException('At least one image is required.');
        }

        if ($isEdit) {
            Database::update('pottery', $data, 'id = :id', ['id' => $pieceId]);
            $finalId = $pieceId;
        } else {
            $data['image_path'] = '';
            $finalId = Database::insert('pottery', $data);
        }

        // Append new uploads after existing images.
        $maxSort = count($existingImages);
        foreach ($newUploads as $i => $upload) {
            $isFirstEverImage = !$isEdit && $i === 0;
            Database::query(
                "INSERT INTO pottery_images (pottery_id, image_path, image_thumb, sort_order, is_primary)
                 VALUES (?,?,?,?,?)",
                [$finalId, $upload['path'], $upload['thumb'], $maxSort + $i, $isFirstEverImage ? 1 : 0]
            );
        }

        // Caller-selected primary takes precedence.
        $primaryImgId = (int)($_POST['primary_image_id'] ?? 0);
        if ($primaryImgId > 0) {
            Database::query("UPDATE pottery_images SET is_primary = 0 WHERE pottery_id = ?", [$finalId]);
            Database::query(
                "UPDATE pottery_images SET is_primary = 1 WHERE id = ? AND pottery_id = ?",
                [$primaryImgId, $finalId]
            );
        } elseif (!$isEdit && !empty($newUploads)) {
            // First newly-uploaded image becomes primary on a fresh piece.
            $first = Database::fetchOne(
                "SELECT * FROM pottery_images WHERE pottery_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1",
                [$finalId]
            );
            if ($first) {
                Database::query("UPDATE pottery_images SET is_primary = 1 WHERE id = ?", [$first['id']]);
            }
        }

        // Sync the pottery row's image_path/thumb to whichever image is now primary.
        $primary = Database::fetchOne(
            "SELECT image_path, image_thumb FROM pottery_images
              WHERE pottery_id = ?
              ORDER BY is_primary DESC, sort_order ASC, id ASC
              LIMIT 1",
            [$finalId]
        );
        if ($primary) {
            Database::query(
                "UPDATE pottery SET image_path = ?, image_thumb = ? WHERE id = ?",
                [$primary['image_path'], $primary['image_thumb'], $finalId]
            );
        }

        flash('success', $isEdit ? 'Piece updated!' : 'Piece added successfully!');
        redirect(SITE_URL . '/admin/pottery/add.php?id=' . $finalId);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }

    if ($isEdit) {
        $existingImages = Database::fetchAll(
            "SELECT * FROM pottery_images WHERE pottery_id = ? ORDER BY sort_order ASC, id ASC",
            [$pieceId]
        );
    }
}

$formData = $_POST + ($piece ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit Piece' : 'Add Piece' ?> — Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/css/admin.css">
    <style>
        .img-gallery { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1rem; }
        .img-gallery-item { position: relative; width: 130px; border-radius: 8px; overflow: hidden; border: 2px solid var(--cream-dk); cursor: pointer; transition: border-color .2s; }
        .img-gallery-item img { width: 100%; height: 110px; object-fit: cover; display: block; }
        .img-gallery-item.is-primary { border-color: var(--clay); }
        .img-gallery-item .img-labels { padding: .3rem .4rem; background: var(--cream); display: flex; gap: .3rem; align-items: center; justify-content: space-between; }
        .primary-indicator { font-size: .6rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--clay); }
        .set-primary-btn { font-size: .65rem; color: var(--ash); background: none; border: 1px solid var(--cream-dk); border-radius: 4px; padding: .1rem .35rem; cursor: pointer; }
        .set-primary-btn:hover { border-color: var(--clay); color: var(--clay); }
        .delete-img-btn { position: absolute; top: 4px; right: 4px; background: rgba(0,0,0,.65); color: #fff; border: none; width: 22px; height: 22px; border-radius: 50%; font-size: .85rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .delete-img-btn:hover { background: #c0392b; }
        .img-add-more { width: 130px; height: 142px; border: 2px dashed var(--cream-dk); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: .3rem; cursor: pointer; color: var(--ash); font-size: .75rem; text-align: center; transition: border-color .2s, color .2s; }
        .img-add-more:hover { border-color: var(--clay); color: var(--clay); }
        .img-add-more svg { width: 24px; height: 24px; }
        .new-preview-item { position: relative; width: 130px; border-radius: 8px; overflow: hidden; border: 2px dashed var(--clay); }
        .new-preview-item img { width: 100%; height: 110px; object-fit: cover; display: block; }
        .new-preview-item .new-badge { background: rgba(212,168,32,.9); color: #fff; font-size: .6rem; font-weight: 700; text-align: center; padding: .25rem; letter-spacing: .06em; text-transform: uppercase; }
        .new-preview-item .remove-new-btn { position: absolute; top: 4px; right: 4px; background: rgba(0,0,0,.65); color: #fff; border: none; width: 22px; height: 22px; border-radius: 50%; font-size: .85rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1><?= $isEdit ? 'Edit: ' . e($piece['title']) : 'Add Pottery Piece' ?></h1>
            <a href="/admin/pottery/index.php" class="admin-btn">← Back</a>
        </div>
        <?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="admin-form" id="pieceForm">
            <?= csrf_field() ?>
            <input type="hidden" name="primary_image_id" id="primaryImageId" value="">

            <div class="form-grid">
                <div class="form-group form-group--full">
                    <label>Title *</label>
                    <input type="text" name="title" required value="<?= e($formData['title'] ?? '') ?>" placeholder="e.g. Speckled Stoneware Mug">
                </div>
                <div class="form-group form-group--full">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Describe this piece..."><?= e($formData['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Technique</label>
                    <input type="text" name="technique" value="<?= e($formData['technique'] ?? '') ?>" placeholder="e.g. Wheel-thrown">
                </div>
                <div class="form-group">
                    <label>Dimensions</label>
                    <input type="text" name="dimensions" value="<?= e($formData['dimensions'] ?? '') ?>" placeholder="e.g. 10cm H × 8cm W">
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <input type="number" name="year" value="<?= e($formData['year'] ?? date('Y')) ?>" min="1900" max="<?= date('Y') ?>">
                </div>
                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" value="<?= e($formData['sort_order'] ?? '0') ?>">
                    <small>Lower numbers appear first</small>
                </div>

                <div class="form-group form-group--full">
                    <label>
                        Photos <?= $isEdit ? '' : '*' ?>
                        <small style="font-weight:400;color:var(--ash)">
                            <?= $isEdit ? 'Click "Set cover" to change primary image. Dashed border = new uploads.' : 'First image is the cover. Up to 10.' ?>
                        </small>
                    </label>
                    <div class="img-gallery" id="imgGallery">
                        <?php foreach ($existingImages as $img): ?>
                        <div class="img-gallery-item <?= $img['is_primary'] ? 'is-primary' : '' ?>" data-img-id="<?= $img['id'] ?>">
                            <img src="/uploads/<?= e($img['image_thumb'] ?? $img['image_path']) ?>" alt="">
                            <button type="button" class="delete-img-btn"
                                onclick="deleteImage(<?= $img['id'] ?>, <?= $pieceId ?>)"
                                title="Delete image">×</button>
                            <div class="img-labels">
                                <?php if ($img['is_primary']): ?>
                                    <span class="primary-indicator">★ Cover</span>
                                <?php else: ?>
                                    <button type="button" class="set-primary-btn"
                                        onclick="setPrimary(<?= $img['id'] ?>)">Set cover</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div id="newPreviews"></div>

                        <div class="img-add-more" onclick="document.getElementById('imgPicker').click()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <?= $isEdit ? 'Add more' : 'Add photos' ?>
                        </div>
                    </div>
                    <input type="file" id="imgPicker" accept="image/*" multiple style="display:none">
                    <div id="fileInputContainer"></div>
                    <small style="color:var(--ash);margin-top:.4rem;display:block">JPG, PNG, WebP — max 10MB each</small>
                </div>

                <div class="form-group form-group--full">
                    <label class="checkbox-label">
                        <input type="checkbox" name="featured" value="1" <?= !empty($formData['featured']) ? 'checked' : '' ?>>
                        <span>Feature on homepage</span>
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="admin-btn admin-btn--primary"><?= $isEdit ? 'Save Changes' : 'Add Piece' ?></button>
                <a href="/admin/pottery/index.php" class="admin-btn">Cancel</a>
            </div>
        </form>
    </div>
</main>

<script>
const CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
const IS_EDIT    = <?= $isEdit ? 'true' : 'false' ?>;

// ── Set primary (existing image) ──────────────────────────
function setPrimary(imgId) {
    document.getElementById('primaryImageId').value = imgId;
    document.querySelectorAll('.img-gallery-item').forEach(el => {
        el.classList.remove('is-primary');
        const lbl = el.querySelector('.img-labels');
        if (!lbl) return;
        if (parseInt(el.dataset.imgId) === imgId) {
            lbl.innerHTML = '<span class="primary-indicator">★ Cover</span>';
            el.classList.add('is-primary');
        } else {
            const existingId = parseInt(el.dataset.imgId);
            lbl.innerHTML = `<button type="button" class="set-primary-btn" onclick="setPrimary(${existingId})">Set cover</button>`;
        }
    });
}

// ── Delete existing image ─────────────────────────────────
function deleteImage(imgId, pieceId) {
    if (!confirm('Delete this image?')) return;
    fetch(`/admin/pottery/delete-image.php?img_id=${imgId}&piece_id=${pieceId}&csrf=${encodeURIComponent(CSRF_TOKEN)}`, { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const el = document.querySelector(`.img-gallery-item[data-img-id="${imgId}"]`);
                if (el) el.remove();
            } else {
                alert(data.error || 'Delete failed');
            }
        });
}

// ── New image uploads preview ─────────────────────────────
const MAX_NEW   = 10;
const newFiles  = [];
const picker    = document.getElementById('imgPicker');
const previews  = document.getElementById('newPreviews');
const container = document.getElementById('fileInputContainer');

picker.addEventListener('change', () => {
    Array.from(picker.files).forEach(f => {
        if (!IS_EDIT && newFiles.length >= MAX_NEW) return;
        newFiles.push(f);
    });
    picker.value = '';
    renderPreviews();
    syncFiles();
});

function renderPreviews() {
    previews.innerHTML = '';
    newFiles.forEach((f, i) => {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'new-preview-item';
            const showCover = !IS_EDIT && i === 0;
            div.innerHTML = `<img src="${e.target.result}">
                <button type="button" class="remove-new-btn" onclick="removeNew(${i})">×</button>
                <div class="new-badge">${showCover ? 'Cover' : 'New'}</div>`;
            previews.appendChild(div);
        };
        reader.readAsDataURL(f);
    });
}

function removeNew(idx) {
    newFiles.splice(idx, 1);
    renderPreviews();
    syncFiles();
}

function syncFiles() {
    container.innerHTML = '';
    if (newFiles.length === 0) return;
    const dt = new DataTransfer();
    newFiles.forEach(f => dt.items.add(f));
    const inp = document.createElement('input');
    inp.type = 'file'; inp.name = 'images[]'; inp.multiple = true; inp.style.display = 'none';
    container.appendChild(inp);
    try { inp.files = dt.files; } catch(e) {}
}

document.getElementById('pieceForm').addEventListener('submit', e => {
    if (!IS_EDIT && newFiles.length === 0) {
        e.preventDefault();
        alert('Please add at least one photo.');
        return;
    }
    syncFiles();
});
</script>
</body>
</html>
