<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$category = trim($_GET['category'] ?? '');

if ($category !== '') {
    $templates = Database::fetchAll(
        "SELECT * FROM pottery_templates WHERE category = ? ORDER BY sort_order ASC, created_at DESC",
        [$category]
    );
} else {
    $templates = Database::fetchAll(
        "SELECT * FROM pottery_templates ORDER BY sort_order ASC, created_at DESC"
    );
}

// Attach files to each template
$templateFiles = [];
if (!empty($templates)) {
    $ids = implode(',', array_map('intval', array_column($templates, 'id')));
    $rows = Database::fetchAll(
        "SELECT * FROM pottery_template_files WHERE template_id IN ($ids) ORDER BY template_id ASC, sort_order ASC"
    );
    foreach ($rows as $row) {
        $templateFiles[$row['template_id']][] = $row;
    }
}

$categories = Database::fetchAll(
    "SELECT DISTINCT category FROM pottery_templates WHERE category != '' ORDER BY category ASC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Templates — <?= e(setting('site_name')) ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon-512.png">
    <link rel="apple-touch-icon" href="/favicon-512.png">
    <style>
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }
        .template-card {
            background: var(--cream);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,.07);
            display: flex;
            flex-direction: column;
            transition: transform .2s, box-shadow .2s;
        }
        .template-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.12); }
        .template-card__preview {
            aspect-ratio: 4/3;
            background: var(--cream-dk);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .template-card__preview img { width: 100%; height: 100%; object-fit: cover; }
        .template-card__preview-placeholder {
            display: flex; flex-direction: column; align-items: center; gap: .5rem; color: var(--ash);
        }
        .template-card__preview-placeholder svg { width: 48px; height: 48px; opacity: .35; }
        .template-card__preview-placeholder span { font-size: .75rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; opacity: .5; }
        .template-card__body { padding: 1.25rem; flex: 1; display: flex; flex-direction: column; gap: .5rem; }
        .template-card__meta { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
        .template-card__badge {
            font-size: .65rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
            background: var(--clay); color: #fff; padding: .15rem .45rem; border-radius: 3px;
        }
        .template-card__badge--count { background: var(--ink); }
        .template-card__category { font-size: .75rem; color: var(--ash); }
        .template-card__title { font-family: 'Playfair Display', serif; font-size: 1.1rem; line-height: 1.3; color: var(--ink); }
        .template-card__desc { font-size: .85rem; color: var(--ash); line-height: 1.5; flex: 1; }
        .template-card__files { border-top: 1px solid var(--cream-dk); }
        .template-file-row {
            display: flex; align-items: center; gap: .65rem;
            padding: .65rem 1.25rem; border-bottom: 1px solid var(--cream-dk);
        }
        .template-file-row:last-child { border-bottom: none; }
        .template-file-row__icon svg { width: 16px; height: 16px; color: var(--ash); display: block; flex-shrink: 0; }
        .template-file-row__info { flex: 1; min-width: 0; }
        .template-file-row__label { font-size: .82rem; font-weight: 600; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .template-file-row__name { font-size: .72rem; color: var(--ash); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .template-file-row__ext { font-size: .62rem; font-weight: 700; text-transform: uppercase; color: var(--clay); flex-shrink: 0; }
        .template-file-row__dl {
            display: inline-flex; align-items: center; gap: .3rem;
            background: var(--clay); color: #fff; text-decoration: none;
            padding: .35rem .75rem; border-radius: 4px; font-size: .78rem; font-weight: 600;
            transition: background .2s; flex-shrink: 0;
        }
        .template-file-row__dl:hover { background: var(--clay-dk, #b5652a); color: #fff; }
        .template-file-row__dl svg { width: 13px; height: 13px; }
        .template-card__footer { padding: .75rem 1.25rem; border-top: 1px solid var(--cream-dk); }
        .template-card__downloads { font-size: .72rem; color: var(--ash); }
        .templates-filters { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 2rem; }
        .templates-filters a {
            padding: .35rem .85rem; border-radius: 20px; font-size: .8rem; font-weight: 600;
            border: 1.5px solid var(--clay); color: var(--clay); text-decoration: none;
            transition: background .15s, color .15s;
        }
        .templates-filters a.active,
        .templates-filters a:hover { background: var(--clay); color: #fff; }
        .templates-empty { text-align: center; padding: 4rem 0; color: var(--ash); }
        .templates-empty svg { width: 56px; height: 56px; opacity: .3; margin-bottom: 1rem; display: block; margin-left: auto; margin-right: auto; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../templates/nav.php'; ?>

<div class="page-header">
    <div class="container">
        <h1 class="page-header__title">Pottery Templates</h1>
        <p class="page-header__sub">Free patterns and guides to download and use in your studio</p>
    </div>
</div>

<section class="section">
    <div class="container">

        <?php if (!empty($categories)): ?>
        <div class="templates-filters">
            <a href="/templates.php" class="<?= $category === '' ? 'active' : '' ?>">All</a>
            <?php foreach ($categories as $cat): ?>
            <a href="/templates.php?category=<?= urlencode($cat['category']) ?>"
               class="<?= $category === $cat['category'] ? 'active' : '' ?>">
                <?= e($cat['category']) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($templates)): ?>
        <div class="templates-empty">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            <p>No templates available yet — check back soon!</p>
        </div>
        <?php else: ?>
        <div class="templates-grid">
            <?php foreach ($templates as $t):
                $files = $templateFiles[$t['id']] ?? [];
                $fileCount = count($files);
            ?>
            <div class="template-card">
                <div class="template-card__preview">
                    <?php if (!empty($t['preview_thumb'])): ?>
                        <img src="/uploads/<?= e($t['preview_thumb']) ?>" alt="<?= e($t['title']) ?>">
                    <?php else: ?>
                        <div class="template-card__preview-placeholder">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                            <span><?= $fileCount ?> file<?= $fileCount !== 1 ? 's' : '' ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="template-card__body">
                    <div class="template-card__meta">
                        <?php if ($fileCount > 0): ?>
                        <span class="template-card__badge"><?= $fileCount ?> file<?= $fileCount !== 1 ? 's' : '' ?></span>
                        <?php endif; ?>
                        <?php if (!empty($t['category'])): ?>
                        <span class="template-card__category"><?= e($t['category']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="template-card__title"><?= e($t['title']) ?></div>
                    <?php if (!empty($t['description'])): ?>
                    <div class="template-card__desc"><?= e($t['description']) ?></div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($files)): ?>
                <div class="template-card__files">
                    <?php foreach ($files as $f): ?>
                    <div class="template-file-row">
                        <span class="template-file-row__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                        </span>
                        <span class="template-file-row__info">
                            <span class="template-file-row__label"><?= e($f['label'] ?: $f['file_name']) ?></span>
                            <?php if (!empty($f['label'])): ?>
                            <span class="template-file-row__name"><?= e($f['file_name']) ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="template-file-row__ext"><?= e(strtoupper($f['file_ext'])) ?></span>
                        <a href="/templates/download.php?file_id=<?= $f['id'] ?>" class="template-file-row__dl">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Download
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="template-card__footer">
                    <span class="template-card__downloads">
                        <?= number_format($t['download_count']) ?> download<?= $t['download_count'] != 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</section>

<?php include __DIR__ . '/../templates/footer.php'; ?>
</body>
</html>
