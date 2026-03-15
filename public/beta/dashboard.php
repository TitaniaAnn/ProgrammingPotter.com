<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/BetaAuth.php';

BetaAuth::requireLogin();

$user  = BetaAuth::getUser();
$flash = getFlash();

$hasDownload = BETA_DOWNLOAD_IOS || BETA_DOWNLOAD_ANDROID || BETA_DOWNLOAD_MAC || BETA_DOWNLOAD_WINDOWS;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beta Dashboard — My Pottery Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/beta/css/beta.css">
</head>
<body class="beta-dashboard-body">

<!-- Sidebar -->
<aside class="beta-sidebar">
    <div class="beta-sidebar__logo">
        <span class="beta-badge">BETA</span>
        <strong>My Pottery Studio</strong>
        <small>Tester Portal</small>
    </div>
    <nav class="beta-sidebar__nav">
        <a href="#download" class="beta-nav-item active" data-section="download">
            <?= iconDownload() ?> Download
        </a>
        <a href="#feedback" class="beta-nav-item" data-section="feedback">
            <?= iconFeedback() ?> Submit Feedback
        </a>
        <a href="#issues" class="beta-nav-item" data-section="issues">
            <?= iconIssues() ?> Issues & Features
        </a>
    </nav>
    <div class="beta-sidebar__footer">
        <span class="beta-sidebar__user"><?= e($user['name']) ?></span>
        <a href="/beta/logout.php" class="beta-sidebar__logout">Sign out</a>
    </div>
</aside>

<!-- Main content -->
<main class="beta-main">

    <!-- Top bar -->
    <header class="beta-topbar">
        <button class="beta-topbar__burger" id="sidebarToggle" aria-label="Menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <span class="beta-topbar__title">Beta Testing Portal</span>
        <span class="beta-topbar__welcome">Welcome, <?= e($user['name']) ?></span>
    </header>

    <div class="beta-content">

        <?php if ($flash): ?>
            <div class="beta-alert beta-alert--<?= e($flash['type']) ?> beta-alert--inline">
                <?= e($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <!-- ── DOWNLOAD SECTION ─────────────────────────────────── -->
        <section id="download" class="beta-section beta-section--active">
            <div class="beta-section__header">
                <h2>Download the App</h2>
                <p>Thank you for being a beta tester! Download the latest version below and let us know what you think.</p>
            </div>

            <?php if ($hasDownload): ?>
            <div class="beta-download-grid">
                <?php if (BETA_DOWNLOAD_IOS): ?>
                <a href="<?= e(BETA_DOWNLOAD_IOS) ?>" class="beta-download-card" target="_blank" rel="noopener">
                    <div class="beta-download-card__icon"><?= iconIOS() ?></div>
                    <div class="beta-download-card__info">
                        <strong>iOS</strong>
                        <span>iPhone &amp; iPad</span>
                    </div>
                    <div class="beta-download-card__arrow">→</div>
                </a>
                <?php endif; ?>

                <?php if (BETA_DOWNLOAD_ANDROID): ?>
                <a href="<?= e(BETA_DOWNLOAD_ANDROID) ?>" class="beta-download-card" target="_blank" rel="noopener">
                    <div class="beta-download-card__icon"><?= iconAndroid() ?></div>
                    <div class="beta-download-card__info">
                        <strong>Android</strong>
                        <span>Phone &amp; Tablet</span>
                    </div>
                    <div class="beta-download-card__arrow">→</div>
                </a>
                <?php endif; ?>

                <?php if (BETA_DOWNLOAD_MAC): ?>
                <a href="<?= e(BETA_DOWNLOAD_MAC) ?>" class="beta-download-card" target="_blank" rel="noopener">
                    <div class="beta-download-card__icon"><?= iconMac() ?></div>
                    <div class="beta-download-card__info">
                        <strong>macOS</strong>
                        <span>Mac App</span>
                    </div>
                    <div class="beta-download-card__arrow">→</div>
                </a>
                <?php endif; ?>

                <?php if (BETA_DOWNLOAD_WINDOWS): ?>
                <a href="<?= e(BETA_DOWNLOAD_WINDOWS) ?>" class="beta-download-card" target="_blank" rel="noopener">
                    <div class="beta-download-card__icon"><?= iconWindows() ?></div>
                    <div class="beta-download-card__info">
                        <strong>Windows</strong>
                        <span>Desktop App</span>
                    </div>
                    <div class="beta-download-card__arrow">→</div>
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="beta-empty-state">
                <p>Download links coming soon — check back shortly!</p>
            </div>
            <?php endif; ?>

            <div class="beta-info-box">
                <strong>Beta Testing Guidelines</strong>
                <ul>
                    <li>Try to break things — that's the point!</li>
                    <li>Report bugs with as much detail as possible (steps to reproduce, device, OS version).</li>
                    <li>Feature requests are welcome via the Feedback tab.</li>
                    <li>Screenshots and screen recordings are very helpful.</li>
                </ul>
            </div>
        </section>

        <!-- ── FEEDBACK SECTION ─────────────────────────────────── -->
        <section id="feedback" class="beta-section">
            <div class="beta-section__header">
                <h2>Submit Feedback</h2>
                <p>Found a bug? Have a feature idea? Let us know and it'll be tracked directly on GitHub.</p>
            </div>

            <form method="POST" action="/beta/submit.php" class="beta-form beta-form--wide">
                <div class="beta-form__row">
                    <div class="beta-form__group">
                        <label>Type</label>
                        <div class="beta-toggle-group">
                            <label class="beta-toggle-option">
                                <input type="radio" name="type" value="bug" checked>
                                <span><?= iconBug() ?> Bug Report</span>
                            </label>
                            <label class="beta-toggle-option">
                                <input type="radio" name="type" value="feature">
                                <span><?= iconFeature() ?> Feature Request</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="beta-form__group">
                    <label for="fb_title">Title <small>(short summary)</small></label>
                    <input type="text" id="fb_title" name="title" required maxlength="255"
                           placeholder="e.g. App crashes when saving a new piece">
                </div>

                <div class="beta-form__group">
                    <label for="fb_body">Description</label>
                    <textarea id="fb_body" name="body" required rows="6"
                              placeholder="For bugs: steps to reproduce, expected vs actual behaviour, device/OS version.
For features: describe the problem it solves and how you'd like it to work."></textarea>
                </div>

                <button type="submit" class="beta-btn beta-btn--primary">Submit →</button>
            </form>
        </section>

        <!-- ── ISSUES SECTION ──────────────────────────────────── -->
        <section id="issues" class="beta-section">
            <div class="beta-section__header">
                <h2>Issues &amp; Features</h2>
                <p>All bugs and feature requests tracked in the GitHub repository.</p>
            </div>

            <div class="beta-issues-toolbar">
                <div class="beta-issues-filters">
                    <button class="beta-filter-btn active" data-filter="all">All</button>
                    <button class="beta-filter-btn" data-filter="open">Open</button>
                    <button class="beta-filter-btn" data-filter="closed">Closed</button>
                    <button class="beta-filter-btn" data-filter="bug">Bugs</button>
                    <button class="beta-filter-btn" data-filter="enhancement">Features</button>
                </div>
                <button class="beta-btn beta-btn--sm" id="refreshIssues">Refresh</button>
            </div>

            <div id="issuesList" class="beta-issues-list">
                <div class="beta-loading">Loading issues…</div>
            </div>
        </section>

    </div><!-- .beta-content -->
</main>

<!-- Sidebar overlay for mobile -->
<div class="beta-sidebar-overlay" id="sidebarOverlay"></div>

<script src="/beta/js/beta.js"></script>
</body>
</html>

<?php
// ── SVG icon helpers (scoped to this file) ──────────────────────────────────

function iconDownload(): string {
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';
}
function iconFeedback(): string {
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>';
}
function iconIssues(): string {
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
}
function iconBug(): string {
    return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2l1.88 1.88M16 2l-1.88 1.88M9 7.13v-.13a3 3 0 016 0v.13"/><path d="M12 20c-3.31 0-6-2.69-6-6v-4a6 6 0 1112 0v4c0 3.31-2.69 6-6 6z"/><path d="M6 10H3M18 10h3M6 15H3M18 15h3"/></svg>';
}
function iconFeature(): string {
    return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
}
function iconIOS(): string {
    return '<svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>';
}
function iconAndroid(): string {
    return '<svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor"><path d="M17.523 15.341a1 1 0 01-1 1H7.477a1 1 0 01-1-1V9a1 1 0 011-1h9.046a1 1 0 011 1v6.341zM7.5 6.5L5.5 4M16.5 6.5L18.5 4M8.5 17.5v2a.5.5 0 001 0v-2M14.5 17.5v2a.5.5 0 001 0v-2M15.477 8H8.523C7.682 8 7 8.682 7 9.523v5.954C7 16.318 7.682 17 8.523 17h6.954C16.318 17 17 16.318 17 15.477V9.523C17 8.682 16.318 8 15.477 8z"/></svg>';
}
function iconMac(): string {
    return '<svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>';
}
function iconWindows(): string {
    return '<svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor"><path d="M3 5.557L10.373 4.5v7.125H3V5.557zM11.25 4.37L21 3v8.625H11.25V4.37zM3 12.375h7.373V19.5L3 18.443v-6.068zM11.25 12.375H21V21l-9.75-1.37v-7.255z"/></svg>';
}
?>
