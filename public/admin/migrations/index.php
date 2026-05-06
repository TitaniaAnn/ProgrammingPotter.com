<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/MigrationRunner.php';
Auth::requireLogin();

$runner  = new MigrationRunner();
$applied = $runner->applied();
$pending = $runner->pending();
$flash   = getFlash();

// Show the next-pending file inline so the admin can sanity-check it before
// pressing Run. We only preview the first pending one to keep the page light.
$preview = null;
if (!empty($pending)) {
    $next = $pending[0];
    try {
        $preview = [
            'version'    => $next,
            'statements' => $runner->statementsFor($next),
        ];
    } catch (\Throwable $e) {
        $preview = ['version' => $next, 'error' => $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migrations — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .mig-summary { display: flex; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
        .mig-pill { background: #fff; border: 1px solid var(--cream-dk); border-radius: 999px; padding: .45rem .85rem; font-size: .82rem; }
        .mig-pill--ok { color: #1b6f31; border-color: #b8deba; background: #edf7ee; }
        .mig-pill--warn { color: #8d3b13; border-color: #f0c7b2; background: #fff1ea; }
        .mig-card { background: #fff; border: 1px solid var(--cream-dk); border-radius: var(--radius); padding: 1rem 1.1rem; margin-bottom: .75rem; }
        .mig-card__head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        .mig-card__name { font-family: monospace; font-size: .92rem; color: var(--soil); }
        .mig-card__meta { font-size: .78rem; color: var(--ash); }
        .mig-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
        .mig-actions form { margin: 0; }
        .source-pill { display: inline-block; padding: .15rem .5rem; border-radius: 999px; font-size: .68rem; text-transform: uppercase; letter-spacing: .06em; border: 1px solid; }
        .source-pill--run  { background: #edf7ee; color: #1b6f31; border-color: #b8deba; }
        .source-pill--mark { background: #eef2f7; color: #5c6b80; border-color: #c8d2df; }
        .preview { margin-top: 1.25rem; }
        .preview pre { background: #f7f8fb; border: 1px solid var(--cream-dk); border-radius: 4px; padding: .75rem; font-size: .78rem; overflow-x: auto; max-height: 420px; }
        .danger-note { font-size: .8rem; color: #8f2c24; margin-top: .35rem; }
        .stmt-count { font-size: .75rem; color: var(--ash); margin-top: .25rem; display: block; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Database Migrations</h1>
            <a href="/admin/settings/schema-health.php" class="admin-btn">Schema Health</a>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
        <?php endif; ?>

        <p style="font-size:.88rem; color:var(--soil); max-width: 60ch;">
            Lists every <code>sql/NNN_*.sql</code> file in the repo. Run a pending migration to apply it to the live
            database, or mark one as already applied if you ran it manually before this UI existed.
            <strong>MySQL DDL implicitly commits</strong> — if a multi-statement migration fails partway through,
            the earlier statements stay applied. Fix the cause and re-run from the failed statement.
        </p>

        <div class="mig-summary">
            <span class="mig-pill">Total: <?= count($applied) + count($pending) ?></span>
            <span class="mig-pill mig-pill--ok">Applied: <?= count($applied) ?></span>
            <span class="mig-pill <?= !empty($pending) ? 'mig-pill--warn' : 'mig-pill--ok' ?>">Pending: <?= count($pending) ?></span>
        </div>

        <h2>Pending</h2>
        <?php if (empty($pending)): ?>
            <p style="color: var(--ash);">Nothing to run — the database is up to date with the repo.</p>
        <?php else: ?>
            <?php foreach ($pending as $i => $version): ?>
                <div class="mig-card">
                    <div class="mig-card__head">
                        <div>
                            <span class="mig-card__name"><?= e($version) ?></span>
                            <?php if ($i === 0): ?>
                                <span class="source-pill source-pill--mark">next</span>
                            <?php endif; ?>
                        </div>
                        <div class="mig-actions">
                            <form method="POST" action="/admin/migrations/run.php"
                                  onsubmit="return confirm('Apply <?= e($version) ?> to the live database? This cannot be undone automatically.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="version" value="<?= e($version) ?>">
                                <input type="hidden" name="action" value="run">
                                <button type="submit" class="admin-btn admin-btn--primary admin-btn--sm">Run</button>
                            </form>
                            <form method="POST" action="/admin/migrations/run.php"
                                  onsubmit="return confirm('Record <?= e($version) ?> as already applied without running it?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="version" value="<?= e($version) ?>">
                                <input type="hidden" name="action" value="mark">
                                <button type="submit" class="admin-btn admin-btn--sm">Mark applied</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($preview): ?>
                <div class="preview">
                    <h3 style="font-size:1rem; margin-bottom:.4rem;">Preview: <?= e($preview['version']) ?></h3>
                    <?php if (isset($preview['error'])): ?>
                        <div class="alert alert--error"><?= e($preview['error']) ?></div>
                    <?php else: ?>
                        <span class="stmt-count"><?= count($preview['statements']) ?> statement<?= count($preview['statements']) === 1 ? '' : 's' ?> will run.</span>
                        <pre><?php
                            foreach ($preview['statements'] as $i => $stmt) {
                                echo '-- statement #' . ($i + 1) . "\n";
                                echo e($stmt) . ";\n\n";
                            }
                        ?></pre>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <h2 style="margin-top:2rem;">Applied</h2>
        <?php if (empty($applied)): ?>
            <p style="color: var(--ash);">No migrations recorded yet.</p>
            <p class="danger-note">
                If your database was set up before this UI existed (e.g. you ran <code>init.sql</code> or
                applied numbered migrations manually), use <em>Mark applied</em> on each pending row that
                has already been run. Do <strong>not</strong> click <em>Run</em> on a migration whose
                changes are already in the database.
            </p>
        <?php else: ?>
            <?php foreach ($applied as $version => $row): ?>
                <div class="mig-card">
                    <div class="mig-card__head">
                        <div>
                            <span class="mig-card__name"><?= e($version) ?></span>
                            <span class="source-pill source-pill--<?= e($row['source']) ?>"><?= e($row['source']) ?></span>
                            <div class="mig-card__meta">
                                Applied <?= e($row['applied_at']) ?><?= $row['applied_by'] ? ' by admin #' . (int) $row['applied_by'] : '' ?>
                                <?= $row['notes'] ? ' — ' . e($row['notes']) : '' ?>
                            </div>
                        </div>
                        <div class="mig-actions">
                            <form method="POST" action="/admin/migrations/run.php"
                                  onsubmit="return confirm('Remove <?= e($version) ?> from the ledger? It will reappear as pending.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="version" value="<?= e($version) ?>">
                                <input type="hidden" name="action" value="unmark">
                                <button type="submit" class="admin-btn admin-btn--sm">Unmark</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
