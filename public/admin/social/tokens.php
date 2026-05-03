<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/AnnouncementSocialMedia.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    if ($action === 'refresh_instagram') {
        try {
            $result = AnnouncementSocialMedia::refreshInstagramToken();
            flash('success', 'Instagram token refreshed. Now valid until ' . $result['expires_at'] . '.');
        } catch (Exception $e) {
            flash('error', 'Refresh failed: ' . $e->getMessage());
        }
    }
    redirect(SITE_URL . '/admin/social/tokens.php');
}

$igToken      = AnnouncementSocialMedia::getInstagramAccessToken();
$igExpiry     = AnnouncementSocialMedia::getInstagramTokenExpiry();
$igRefreshed  = AnnouncementSocialMedia::getInstagramTokenLastRefreshed();
$igAccountId  = defined('INSTAGRAM_BUSINESS_ACCOUNT_ID') ? INSTAGRAM_BUSINESS_ACCOUNT_ID : '';
$now          = new DateTimeImmutable();

$igStatus = 'missing';
$igMessage = 'No Instagram credentials configured. Set INSTAGRAM_ACCESS_TOKEN and INSTAGRAM_BUSINESS_ACCOUNT_ID in .env, then refresh below.';
if (!empty($igToken) && !empty($igAccountId)) {
    if ($igExpiry === null) {
        $igStatus = 'unknown';
        $igMessage = 'Token configured but expiry is unknown. Click Refresh to capture an expiry date going forward.';
    } elseif ($igExpiry < $now) {
        $igStatus = 'expired';
        $igMessage = 'Token expired ' . $igExpiry->format('Y-m-d H:i') . '. Generate a new token from Meta Business Manager and update .env.';
    } else {
        $daysLeft = $igExpiry->diff($now)->days;
        if ($daysLeft <= 7) {
            $igStatus = 'expiring';
            $igMessage = 'Token expires in ' . $daysLeft . ' day' . ($daysLeft === 1 ? '' : 's') . ' (' . $igExpiry->format('Y-m-d') . '). Refresh now.';
        } else {
            $igStatus = 'healthy';
            $igMessage = 'Token valid for another ' . $daysLeft . ' days (expires ' . $igExpiry->format('Y-m-d') . ').';
        }
    }
}
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Tokens — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .token-card { background: #fff; border-radius: 8px; padding: 1.5rem; border: 1px solid var(--cream-dk); margin-bottom: 1rem; }
        .token-status { display: inline-block; padding: .25rem .6rem; border-radius: 4px; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
        .token-status--healthy  { background: #edf7ee; color: #2d6a30; }
        .token-status--expiring { background: #fff8e6; color: #b07d10; }
        .token-status--expired,
        .token-status--missing  { background: #fdf0ef; color: #a33028; }
        .token-status--unknown  { background: #eef2f7; color: #5c6b80; }
        .token-meta { font-size: .85rem; color: var(--ash); margin-top: .5rem; }
        .token-meta dt { display: inline-block; min-width: 140px; font-weight: 600; color: var(--ink); }
        .token-meta dd { display: inline; margin: 0; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Social Tokens</h1>
        </div>

        <?php if ($flash): ?>
        <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
        <?php endif; ?>

        <div class="token-card">
            <h2 style="margin-bottom: .75rem;">Instagram</h2>
            <span class="token-status token-status--<?= e($igStatus) ?>"><?= e($igStatus) ?></span>
            <p style="margin-top: .75rem;"><?= e($igMessage) ?></p>

            <dl class="token-meta">
                <div><dt>Account ID:</dt> <dd><?= $igAccountId ? e($igAccountId) : '<em>not set</em>' ?></dd></div>
                <div><dt>Expires at:</dt> <dd><?= $igExpiry ? e($igExpiry->format('Y-m-d H:i')) : '<em>unknown</em>' ?></dd></div>
                <div><dt>Last refreshed:</dt> <dd><?= $igRefreshed ? e($igRefreshed->format('Y-m-d H:i')) : '<em>never</em>' ?></dd></div>
            </dl>

            <form method="POST" style="margin-top: 1rem;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="refresh_instagram">
                <button type="submit" class="admin-btn admin-btn--primary"<?= empty($igToken) ? ' disabled' : '' ?>>
                    Refresh Instagram Token
                </button>
                <small style="margin-left: 1rem; color: var(--ash);">
                    Calls Instagram's refresh endpoint and stores the extended (60-day) token in the database.
                </small>
            </form>
        </div>

        <div class="token-card">
            <h2 style="margin-bottom: .75rem;">TikTok</h2>
            <span class="token-status token-status--missing">disabled</span>
            <p style="margin-top: .75rem;">TikTok image posting is not currently supported — the Content Posting API requires video uploads. The TikTok option is hidden from the announcements UI.</p>
        </div>
    </div>
</main>
</body>
</html>
