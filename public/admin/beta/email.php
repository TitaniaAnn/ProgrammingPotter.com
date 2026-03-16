<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

$audience = $_GET['audience'] ?? 'all';
$counts = [
    'all'     => Database::fetchOne("SELECT COUNT(*) n FROM beta_users WHERE approved=1")['n'] ?? 0,
    'android' => Database::fetchOne("SELECT COUNT(*) n FROM beta_users WHERE approved=1 AND platform IN ('android','both')")['n'] ?? 0,
    'ios'     => Database::fetchOne("SELECT COUNT(*) n FROM beta_users WHERE approved=1 AND platform IN ('ios','both')")['n'] ?? 0,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Testers — Admin</title>
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../partials/sidebar.php'; ?>
<main class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>Email Testers</h1>
        </div>

        <div class="admin-card" style="max-width:680px">
            <form method="POST" action="/admin/beta/send-email.php">

                <div class="form-group" style="margin-bottom:1.5rem">
                    <label class="form-label">Send To</label>
                    <div style="display:flex;gap:.75rem;flex-wrap:wrap">
                        <?php foreach ([
                            'all'     => "All Testers ({$counts['all']})",
                            'android' => "Android Testers ({$counts['android']})",
                            'ios'     => "iOS Testers ({$counts['ios']})",
                        ] as $val => $label): ?>
                        <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;font-size:.9rem">
                            <input type="radio" name="audience" value="<?= $val ?>"
                                   <?= ($audience === $val) ? 'checked' : '' ?>>
                            <?= $label ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:1.25rem">
                    <label class="form-label" for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" required maxlength="255"
                           class="form-input" placeholder="e.g. New beta build available — v1.2">
                </div>

                <div class="form-group" style="margin-bottom:1.5rem">
                    <label class="form-label" for="body">Message</label>
                    <textarea id="body" name="body" required rows="10"
                              class="form-input" style="resize:vertical"
                              placeholder="Write your message here. Each tester will receive a personalised greeting with their name."></textarea>
                    <small style="color:var(--ash)">Each email will start with "Hi [Name]," automatically.</small>
                </div>

                <button type="submit" class="btn btn--primary"
                        onclick="return confirm('Send this email to the selected testers?')">
                    Send Emails
                </button>
                <a href="/admin/beta/index.php" class="btn" style="margin-left:.5rem">Cancel</a>
            </form>
        </div>
    </div>
</main>
</body>
</html>
