<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

define('ALLOWED_TEMPLATE_EXTS',  ['pdf', 'svg', 'png', 'jpg', 'jpeg', 'webp', 'zip']);
define('ALLOWED_TEMPLATE_MIMES', [
    'application/pdf', 'image/svg+xml', 'image/png',
    'image/jpeg', 'image/webp', 'application/zip',
    'application/x-zip-compressed',
]);
define('MAX_TEMPLATE_SIZE', 50 * 1024 * 1024);

function uploadTemplateFile(array $file): array {
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('File upload error: ' . $file['name']);
    if ($file['size'] > MAX_TEMPLATE_SIZE) throw new RuntimeException($file['name'] . ' exceeds 50MB limit.');

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_TEMPLATE_EXTS, true)) {
        throw new RuntimeException('File type not allowed: ' . $ext . '. Accepted: ' . implode(', ', ALLOWED_TEMPLATE_EXTS));
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ALLOWED_TEMPLATE_MIMES, true)) {
        throw new RuntimeException('File MIME type not allowed: ' . $file['name']);
    }

    $dir = ROOT_PATH . '/public/uploads/templates/files/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $safeName = 'template_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $safeName)) {
        throw new RuntimeException('Failed to save file: ' . $file['name']);
    }

    return [
        'file_path' => 'templates/files/' . $safeName,
        'file_name' => $file['name'],
        'file_size' => $file['size'],
        'file_ext'  => $ext,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $files = $_FILES['template_files'] ?? [];
        $hasFile = false;
        foreach ($files['error'] ?? [] as $err) {
            if ($err === UPLOAD_ERR_OK) { $hasFile = true; break; }
        }
        if (!$hasFile) throw new RuntimeException('At least one template file is required.');

        $data = [
            'title'       => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'category'    => trim($_POST['category'] ?? ''),
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
        ];
        if (empty($data['title'])) throw new RuntimeException('Title is required.');

        // Optional preview image
        if (!empty($_FILES['preview']['name']) && $_FILES['preview']['error'] === UPLOAD_ERR_OK) {
            $preview = ImageUpload::upload($_FILES['preview'], 'templates/previews');
            $data['preview_path']  = $preview['path'];
            $data['preview_thumb'] = $preview['thumb'];
        }

        $templateId = Database::insert('pottery_templates', $data);

        // Upload each file
        $labels = $_POST['file_labels'] ?? [];
        $count  = count($files['name']);
        $idx    = 0;
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK || empty($files['name'][$i])) continue;
            $single = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
            $uploaded = uploadTemplateFile($single);
            Database::insert('pottery_template_files', [
                'template_id' => $templateId,
                'file_path'   => $uploaded['file_path'],
                'file_name'   => $uploaded['file_name'],
                'file_size'   => $uploaded['file_size'],
                'file_ext'    => $uploaded['file_ext'],
                'label'       => trim($labels[$i] ?? ''),
                'sort_order'  => $idx++,
            ]);
        }

        flash('success', 'Template added successfully!');
        redirect(SITE_URL . '/admin/templates/index.php');
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
    <title>Add Template — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .file-drop {
            border: 2px dashed var(--cream-dk); border-radius: 8px;
            padding: 2rem; text-align: center; cursor: pointer;
            transition: border-color .2s, background .2s;
        }
        .file-drop:hover, .file-drop.dragover { border-color: var(--clay); background: rgba(197,120,78,.04); }
        .file-drop svg { width: 36px; height: 36px; color: var(--ash); margin-bottom: .5rem; display: block; margin-left: auto; margin-right: auto; }
        .file-drop__label { font-size: .9rem; color: var(--ash); }
        .file-drop__label strong { color: var(--clay); }
        .file-queue { margin-top: 1rem; display: flex; flex-direction: column; gap: .5rem; }
        .file-queue-item {
            display: flex; align-items: center; gap: .75rem;
            background: var(--cream-dk); border-radius: 6px; padding: .6rem .85rem;
        }
        .file-queue-item__icon { color: var(--clay); flex-shrink: 0; }
        .file-queue-item__icon svg { width: 18px; height: 18px; display: block; }
        .file-queue-item__name { font-size: .83rem; font-weight: 600; color: var(--ink); flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .file-queue-item__ext { font-size: .68rem; font-weight: 700; text-transform: uppercase; color: var(--clay); flex-shrink: 0; }
        .file-queue-item__label input { font-size: .8rem; padding: .25rem .5rem; border: 1px solid var(--cream-dk); border-radius: 4px; width: 140px; }
        .file-queue-item__remove { background: none; border: none; cursor: pointer; color: var(--ash); padding: 2px; flex-shrink: 0; line-height: 1; font-size: 1.1rem; }
        .file-queue-item__remove:hover { color: #c0392b; }
        .preview-wrap { display: flex; align-items: flex-start; gap: 1rem; }
        .preview-thumb-box { width: 110px; height: 110px; border-radius: 6px; overflow: hidden; border: 2px solid var(--cream-dk); flex-shrink: 0; background: var(--cream-dk); display: none; }
        .preview-thumb-box img { width: 100%; height: 100%; object-fit: cover; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Add Template</h1>
            <a href="/admin/templates/index.php" class="admin-btn">← Back</a>
        </div>

        <?php if (!empty($error)): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="admin-form" id="templateForm">
            <div class="form-grid">
                <div class="form-group form-group--full">
                    <label>Title *</label>
                    <input type="text" name="title" required value="<?= e($_POST['title'] ?? '') ?>" placeholder="e.g. Cylinder Base Template">
                </div>
                <div class="form-group form-group--full">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Brief description of what this template is for..."><?= e($_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" value="<?= e($_POST['category'] ?? '') ?>" placeholder="e.g. Wheel Throwing, Hand Building">
                    <small>Used for filtering on the public page</small>
                </div>
                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" value="<?= e($_POST['sort_order'] ?? '0') ?>">
                    <small>Lower numbers appear first</small>
                </div>

                <div class="form-group form-group--full">
                    <label>Template Files * <small style="font-weight:400;color:var(--ash)">PDF, SVG, PNG, JPG, WebP, ZIP — max 50MB each. Add a label to each file (optional).</small></label>
                    <div class="file-drop" id="fileDrop">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        <div class="file-drop__label"><strong>Click to choose files</strong> or drag &amp; drop — multiple files allowed</div>
                    </div>
                    <div class="file-queue" id="fileQueue"></div>
                    <div id="fileInputContainer"></div>
                    <input type="file" id="filePicker" accept=".pdf,.svg,.png,.jpg,.jpeg,.webp,.zip" multiple style="display:none">
                </div>

                <div class="form-group form-group--full">
                    <label>Preview Image <small style="font-weight:400;color:var(--ash)">Optional — thumbnail shown on the templates page</small></label>
                    <div class="preview-wrap">
                        <div class="preview-thumb-box" id="previewBox"><img id="previewImg" src="" alt=""></div>
                        <div style="flex:1">
                            <div class="file-drop" style="padding:1rem" id="previewDrop">
                                <div class="file-drop__label"><strong>Click to choose</strong> preview image</div>
                                <div id="previewChosen" style="font-size:.82rem;font-weight:600;color:var(--ink);margin-top:.3rem"></div>
                            </div>
                            <input type="file" id="previewFile" name="preview" accept="image/*" style="display:none">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="admin-btn admin-btn--primary">Add Template</button>
                <a href="/admin/templates/index.php" class="admin-btn">Cancel</a>
            </div>
        </form>
    </div>
</main>
<script>
const allFiles   = [];
const picker     = document.getElementById('filePicker');
const fileDrop   = document.getElementById('fileDrop');
const fileQueue  = document.getElementById('fileQueue');
const container  = document.getElementById('fileInputContainer');

fileDrop.addEventListener('click', () => picker.click());
fileDrop.addEventListener('dragover', e => { e.preventDefault(); fileDrop.classList.add('dragover'); });
fileDrop.addEventListener('dragleave', () => fileDrop.classList.remove('dragover'));
fileDrop.addEventListener('drop', e => {
    e.preventDefault(); fileDrop.classList.remove('dragover');
    addFiles(Array.from(e.dataTransfer.files));
});
picker.addEventListener('change', () => {
    addFiles(Array.from(picker.files));
    picker.value = '';
});

function addFiles(files) {
    files.forEach(f => allFiles.push({ file: f, label: '' }));
    renderQueue();
    syncInput();
}

function removeFile(idx) {
    allFiles.splice(idx, 1);
    renderQueue();
    syncInput();
}

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
            <button type="button" class="file-queue-item__remove" data-idx="${idx}" title="Remove">×</button>
        `;
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
    const dt  = new DataTransfer();
    allFiles.forEach(item => dt.items.add(item.file));
    const inp = document.createElement('input');
    inp.type = 'file'; inp.name = 'template_files[]'; inp.multiple = true; inp.style.display = 'none';
    container.appendChild(inp);
    try { inp.files = dt.files; } catch(e) {}

    // Write labels as hidden inputs matching order
    allFiles.forEach((item, i) => {
        const h = document.createElement('input');
        h.type = 'hidden'; h.name = 'file_labels[]'; h.value = item.label;
        container.appendChild(h);
    });
}

document.getElementById('templateForm').addEventListener('submit', e => {
    if (!allFiles.length) { e.preventDefault(); alert('Please add at least one template file.'); return; }
    syncInput();
});

// Preview image
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
