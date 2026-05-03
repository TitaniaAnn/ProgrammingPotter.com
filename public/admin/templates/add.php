<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/TemplateFileUploader.php';
Auth::requireLogin();

$isEdit        = !empty($_GET['id']);
$templateId    = $isEdit ? (int) $_GET['id'] : 0;
$template      = null;
$existingFiles = [];

if ($isEdit) {
    $template = Database::fetchOne("SELECT * FROM pottery_templates WHERE id = ?", [$templateId]);
    if (!$template) {
        flash('error', 'Template not found.');
        redirect(SITE_URL . '/admin/templates/index.php');
    }
    $existingFiles = Database::fetchAll(
        "SELECT * FROM pottery_template_files WHERE template_id = ? ORDER BY sort_order ASC",
        [$templateId]
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    try {
        $data = [
            'title'       => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'category'    => trim($_POST['category'] ?? ''),
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
        ];
        if (empty($data['title'])) {
            throw new RuntimeException('Title is required.');
        }

        $newFiles = MultiFileUpload::parse($_FILES['template_files'] ?? null);

        if (!$isEdit && empty($newFiles)) {
            throw new RuntimeException('At least one template file is required.');
        }

        // Optional preview image (handled differently for add vs edit).
        $previewUpload = null;
        if (!empty($_FILES['preview']['name']) && $_FILES['preview']['error'] === UPLOAD_ERR_OK) {
            $previewUpload = ImageUpload::upload($_FILES['preview'], 'templates/previews');
            $data['preview_path']  = $previewUpload['path'];
            $data['preview_thumb'] = $previewUpload['thumb'];
        }

        if ($isEdit) {
            // Allow explicit removal of preview image.
            if (isset($_POST['remove_preview']) && !empty($template['preview_path'])) {
                ImageUpload::delete($template['preview_path']);
                $data['preview_path']  = '';
                $data['preview_thumb'] = '';
            } elseif ($previewUpload && !empty($template['preview_path'])) {
                // Replace: nuke the old file we just rendered redundant.
                ImageUpload::delete($template['preview_path']);
            }

            Database::update('pottery_templates', $data, 'id = :wid', ['wid' => $templateId]);
            $finalId = $templateId;

            // Update labels on existing files.
            foreach ($_POST['existing_label'] ?? [] as $fileId => $label) {
                Database::update(
                    'pottery_template_files',
                    ['label' => trim($label)],
                    'id = :wid',
                    ['wid' => (int) $fileId]
                );
            }
        } else {
            $finalId = Database::insert('pottery_templates', $data);
        }

        // Append new files.
        $labels    = $_POST['file_labels'] ?? [];
        $sortStart = count($existingFiles);
        $offset    = 0;
        foreach ($newFiles as $i => $single) {
            $uploaded = TemplateFileUploader::upload($single);
            Database::insert('pottery_template_files', [
                'template_id' => $finalId,
                'file_path'   => $uploaded['file_path'],
                'file_name'   => $uploaded['file_name'],
                'file_size'   => $uploaded['file_size'],
                'file_ext'    => $uploaded['file_ext'],
                'label'       => trim($labels[$i] ?? ''),
                'sort_order'  => $sortStart + $offset,
            ]);
            $offset++;
        }

        flash('success', $isEdit ? 'Template updated.' : 'Template added successfully!');
        redirect(SITE_URL . '/admin/templates/add.php?id=' . $finalId);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }

    // Reload after potential changes so the rendered view stays consistent.
    if ($isEdit) {
        $existingFiles = Database::fetchAll(
            "SELECT * FROM pottery_template_files WHERE template_id = ? ORDER BY sort_order ASC",
            [$templateId]
        );
    }
}

$formData = $_POST + ($template ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit Template' : 'Add Template' ?> — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .file-drop {
            border: 2px dashed var(--cream-dk); border-radius: 8px;
            padding: 1.5rem; text-align: center; cursor: pointer;
            transition: border-color .2s, background .2s;
        }
        .file-drop:hover, .file-drop.dragover { border-color: var(--clay); background: rgba(197,120,78,.04); }
        .file-drop svg { width: 36px; height: 36px; color: var(--ash); margin-bottom: .5rem; display: block; margin-left: auto; margin-right: auto; }
        .file-drop__label { font-size: .88rem; color: var(--ash); }
        .file-drop__label strong { color: var(--clay); }
        .file-list { display: flex; flex-direction: column; gap: .5rem; margin-bottom: .75rem; }
        .file-list-item, .file-queue-item {
            display: flex; align-items: center; gap: .75rem;
            border-radius: 6px; padding: .6rem .85rem;
        }
        .file-list-item { background: var(--cream-dk); }
        .file-queue-item { background: #eef6ee; }
        .file-list-item__icon svg, .file-queue-item__icon svg { width: 18px; height: 18px; display: block; color: var(--clay); }
        .file-list-item__name, .file-queue-item__name { font-size: .83rem; font-weight: 600; flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--ink); }
        .file-list-item__ext, .file-queue-item__ext { font-size: .68rem; font-weight: 700; text-transform: uppercase; color: var(--clay); flex-shrink: 0; }
        .file-list-item__label input, .file-queue-item__label input { font-size: .8rem; padding: .25rem .5rem; border: 1px solid var(--cream-dk); border-radius: 4px; width: 150px; }
        .file-list-item__del, .file-queue-item__remove { background: none; border: none; cursor: pointer; color: var(--ash); padding: 2px 4px; font-size: .85rem; border-radius: 3px; white-space: nowrap; }
        .file-list-item__del:hover, .file-queue-item__remove:hover { background: #fce; color: #c0392b; }
        .file-queue { display: flex; flex-direction: column; gap: .5rem; margin-top: .75rem; }
        .preview-wrap, .preview-row { display: flex; align-items: flex-start; gap: 1rem; margin-bottom: .75rem; }
        .preview-thumb-box { width: 110px; height: 110px; border-radius: 6px; overflow: hidden; border: 2px solid var(--cream-dk); flex-shrink: 0; background: var(--cream-dk); }
        .preview-thumb-box img { width: 100%; height: 100%; object-fit: cover; }
        .preview-thumb-box.empty { display: none; }
        .section-label { font-weight: 700; font-size: .85rem; color: var(--ash); text-transform: uppercase; letter-spacing: .06em; margin-bottom: .5rem; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1><?= $isEdit ? 'Edit Template' : 'Add Template' ?></h1>
            <a href="/admin/templates/index.php" class="admin-btn">← Back</a>
        </div>

        <?php if (!empty($error)): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="admin-form" id="templateForm">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="form-group form-group--full">
                    <label>Title *</label>
                    <input type="text" name="title" required value="<?= e($formData['title'] ?? '') ?>" placeholder="e.g. Cylinder Base Template">
                </div>
                <div class="form-group form-group--full">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Brief description of what this template is for..."><?= e($formData['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" value="<?= e($formData['category'] ?? '') ?>" placeholder="e.g. Wheel Throwing, Hand Building">
                    <small>Used for filtering on the public page</small>
                </div>
                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" value="<?= e($formData['sort_order'] ?? '0') ?>">
                    <small>Lower numbers appear first</small>
                </div>

                <div class="form-group form-group--full">
                    <label>
                        Template Files <?= $isEdit ? '' : '*' ?>
                        <small style="font-weight:400;color:var(--ash)">PDF, SVG, PNG, JPG, WebP, ZIP — max 50MB each. Add a label to each file (optional).</small>
                    </label>

                    <?php if ($isEdit && !empty($existingFiles)): ?>
                    <div class="section-label">Current files</div>
                    <div class="file-list">
                        <?php foreach ($existingFiles as $f): ?>
                        <div class="file-list-item">
                            <span class="file-list-item__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>
                            <span class="file-list-item__name" title="<?= e($f['file_name']) ?>"><?= e($f['file_name']) ?></span>
                            <span class="file-list-item__ext"><?= e($f['file_ext']) ?></span>
                            <span class="file-list-item__label">
                                <input type="text" name="existing_label[<?= $f['id'] ?>]" value="<?= e($f['label']) ?>" placeholder="Label (optional)">
                            </span>
                            <button type="button" class="file-list-item__del"
                                    data-file-id="<?= $f['id'] ?>" data-template-id="<?= $templateId ?>">
                                Remove
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="section-label" style="margin-top:.75rem"><?= $isEdit ? 'Add more files' : 'Upload files' ?></div>
                    <div class="file-drop" id="fileDrop">
                        <?php if (!$isEdit): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        <?php endif; ?>
                        <div class="file-drop__label"><strong>Click to choose files</strong> or drag &amp; drop — multiple files allowed</div>
                    </div>
                    <div class="file-queue" id="fileQueue"></div>
                    <div id="fileInputContainer"></div>
                    <input type="file" id="filePicker" accept=".pdf,.svg,.png,.jpg,.jpeg,.webp,.zip" multiple style="display:none">
                </div>

                <div class="form-group form-group--full">
                    <label>Preview Image <small style="font-weight:400;color:var(--ash)">Optional — thumbnail shown on the templates page</small></label>
                    <?php if ($isEdit && !empty($template['preview_thumb'])): ?>
                    <div class="preview-row">
                        <div class="preview-thumb-box">
                            <img src="/uploads/<?= e($template['preview_thumb']) ?>" alt="">
                        </div>
                        <div>
                            <p style="font-size:.82rem;color:var(--ash);margin:0 0 .5rem">Current preview</p>
                            <label class="checkbox-label" style="font-size:.82rem">
                                <input type="checkbox" name="remove_preview" value="1">
                                <span>Remove preview image</span>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="preview-wrap">
                        <div class="preview-thumb-box <?= empty($template['preview_thumb']) ? 'empty' : '' ?>" id="previewBox" style="<?= empty($template['preview_thumb']) ? 'display:none' : '' ?>">
                            <img id="previewImg" src="" alt="">
                        </div>
                        <div style="flex:1">
                            <div class="file-drop" style="padding:1rem" id="previewDrop">
                                <div class="file-drop__label"><strong>Click to <?= !empty($template['preview_thumb']) ? 'replace' : 'choose' ?></strong> preview image</div>
                                <div id="previewChosen" style="font-size:.82rem;font-weight:600;color:var(--ink);margin-top:.3rem"></div>
                            </div>
                            <input type="file" id="previewFile" name="preview" accept="image/*" style="display:none">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="admin-btn admin-btn--primary"><?= $isEdit ? 'Save Changes' : 'Add Template' ?></button>
                <a href="/admin/templates/index.php" class="admin-btn">Cancel</a>
            </div>
        </form>
    </div>
</main>
<script>
const CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
const IS_EDIT    = <?= $isEdit ? 'true' : 'false' ?>;

const allFiles  = [];
const picker    = document.getElementById('filePicker');
const fileDrop  = document.getElementById('fileDrop');
const fileQueue = document.getElementById('fileQueue');
const container = document.getElementById('fileInputContainer');

fileDrop.addEventListener('click', () => picker.click());
fileDrop.addEventListener('dragover', e => { e.preventDefault(); fileDrop.classList.add('dragover'); });
fileDrop.addEventListener('dragleave', () => fileDrop.classList.remove('dragover'));
fileDrop.addEventListener('drop', e => {
    e.preventDefault(); fileDrop.classList.remove('dragover');
    addFiles(Array.from(e.dataTransfer.files));
});
picker.addEventListener('change', () => { addFiles(Array.from(picker.files)); picker.value = ''; });

function addFiles(files)   { files.forEach(f => allFiles.push({ file: f, label: '' })); renderQueue(); syncInput(); }
function removeFile(idx)   { allFiles.splice(idx, 1); renderQueue(); syncInput(); }

function renderQueue() {
    fileQueue.innerHTML = '';
    allFiles.forEach((item, idx) => {
        const ext = item.file.name.split('.').pop().toUpperCase();
        const div = document.createElement('div');
        div.className = 'file-queue-item';
        div.innerHTML = `
            <span class="file-queue-item__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>
            <span class="file-queue-item__name" title="${item.file.name}">${item.file.name}</span>
            <span class="file-queue-item__ext">${ext}</span>
            <span class="file-queue-item__label"><input type="text" placeholder="Label (optional)" value="${item.label}" data-idx="${idx}"></span>
            <button type="button" class="file-queue-item__remove" data-idx="${idx}">×</button>`;
        fileQueue.appendChild(div);
    });
    fileQueue.querySelectorAll('.file-queue-item__label input').forEach(inp => {
        inp.addEventListener('input', () => { allFiles[inp.dataset.idx].label = inp.value; });
    });
    fileQueue.querySelectorAll('.file-queue-item__remove').forEach(btn => {
        btn.addEventListener('click', () => removeFile(parseInt(btn.dataset.idx)));
    });
}

function syncInput() {
    container.innerHTML = '';
    if (!allFiles.length) return;
    const dt = new DataTransfer();
    allFiles.forEach(item => dt.items.add(item.file));
    const inp = document.createElement('input');
    inp.type = 'file'; inp.name = 'template_files[]'; inp.multiple = true; inp.style.display = 'none';
    container.appendChild(inp);
    try { inp.files = dt.files; } catch(e) {}
    allFiles.forEach((item) => {
        const h = document.createElement('input');
        h.type = 'hidden'; h.name = 'file_labels[]'; h.value = item.label;
        container.appendChild(h);
    });
}

document.getElementById('templateForm').addEventListener('submit', e => {
    if (!IS_EDIT && !allFiles.length) {
        e.preventDefault();
        alert('Please add at least one template file.');
        return;
    }
    syncInput();
});

// Delete an existing file (edit mode only).
document.querySelectorAll('.file-list-item__del').forEach(btn => {
    btn.addEventListener('click', () => {
        if (!confirm('Remove this file?')) return;
        const fileId     = btn.dataset.fileId;
        const templateId = btn.dataset.templateId;
        fetch(`/admin/templates/delete-file.php?file_id=${fileId}&template_id=${templateId}&csrf=${encodeURIComponent(CSRF_TOKEN)}`, { method: 'POST' })
            .then(r => r.json())
            .then(d => {
                if (d.success) btn.closest('.file-list-item').remove();
                else alert(d.error || 'Delete failed.');
            });
    });
});

// Preview image.
const previewDrop   = document.getElementById('previewDrop');
const previewFile   = document.getElementById('previewFile');
const previewChosen = document.getElementById('previewChosen');
const previewBox    = document.getElementById('previewBox');
const previewImg    = document.getElementById('previewImg');

previewDrop.addEventListener('click', () => previewFile.click());
previewFile.addEventListener('change', () => {
    if (!previewFile.files[0]) return;
    previewChosen.textContent = previewFile.files[0].name;
    const reader = new FileReader();
    reader.onload = e => { previewImg.src = e.target.result; previewBox.style.display = 'block'; };
    reader.readAsDataURL(previewFile.files[0]);
});
</script>
</body>
</html>
