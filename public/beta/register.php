<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/BetaAuth.php';

if (BetaAuth::isLoggedIn()) {
    redirect('/beta/dashboard.php');
}

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $platform = in_array($_POST['platform'] ?? '', ['android','ios']) ? $_POST['platform'] : 'android';
    $agreed   = !empty($_POST['agreement']);
    $eulaAgreed = !empty($_POST['eula']);

    if (!$agreed || !$eulaAgreed) {
        $error = 'You must agree to both the Beta Test Agreement and the EULA to continue.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            BetaAuth::register($email, $password, $name, $platform);
            $success = true;
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beta Sign Up — My Pottery Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/beta/css/beta.css">
</head>
<body class="beta-auth-body">

<div class="beta-auth-wrap beta-auth-wrap--wide">
    <div class="beta-auth-card">
        <div class="beta-auth-card__logo">
            <span class="beta-badge">BETA</span>
            <h1>My Pottery Studio</h1>
            <p>Request Beta Access</p>
        </div>

        <?php if ($success): ?>
            <div class="beta-alert beta-alert--success">
                Account created! <a href="/beta/login.php">Sign in now →</a>
            </div>
        <?php else: ?>

        <?php if ($error): ?>
            <div class="beta-alert beta-alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="beta-form">
            <div class="beta-form__group">
                <label for="name">Your Name</label>
                <input type="text" id="name" name="name" required
                       value="<?= e($_POST['name'] ?? '') ?>"
                       placeholder="Jane Smith">
            </div>
            <div class="beta-form__group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= e($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com">
            </div>
            <div class="beta-form__group">
                <label for="password">Password <small>(8+ characters)</small></label>
                <input type="password" id="password" name="password" required
                       placeholder="••••••••" minlength="8">
            </div>
            <div class="beta-form__group">
                <label for="confirm">Confirm Password</label>
                <input type="password" id="confirm" name="confirm" required
                       placeholder="••••••••">
            </div>
            <div class="beta-form__group">
                <label>Platform</label>
                <div class="beta-toggle-group">
                    <label class="beta-toggle-option">
                        <input type="radio" name="platform" value="android" required
                               <?= (($_POST['platform'] ?? '') === 'android') ? 'checked' : '' ?>>
                        <span>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:4px"><path d="M17.523 15.341a1 1 0 01-1 1H7.477a1 1 0 01-1-1V9a1 1 0 011-1h9.046a1 1 0 011 1v6.341zM7.5 6.5L5.5 4M16.5 6.5L18.5 4M8.5 17.5v2a.5.5 0 001 0v-2M14.5 17.5v2a.5.5 0 001 0v-2M15.477 8H8.523C7.682 8 7 8.682 7 9.523v5.954C7 16.318 7.682 17 8.523 17h6.954C16.318 17 17 16.318 17 15.477V9.523C17 8.682 16.318 8 15.477 8z"/></svg>
                            Android
                        </span>
                    </label>
                    <label class="beta-toggle-option">
                        <input type="radio" name="platform" value="ios" required
                               <?= (($_POST['platform'] ?? '') === 'ios') ? 'checked' : '' ?>>
                        <span>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:4px"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                            iOS (Apple)
                        </span>
                    </label>
                </div>
            </div>

            <!-- Beta Test Agreement -->
            <div class="beta-form__group">
                <label>Beta Test Agreement</label>
                <div class="beta-agreement-box" id="agreementBox">
                    <div class="beta-agreement-text">
                        <p><strong>My Pottery Studio — Beta Tester Agreement</strong></p>
                        <p>Last updated: <?= date('F j, Y') ?></p>

                        <p>By participating in the My Pottery Studio beta program ("Beta Program"), you agree to the following terms. Please read them carefully.</p>

                        <p><strong>1. Confidentiality</strong><br>
                        The beta app and all related materials are confidential. You agree not to share, publish, post screenshots of, stream, or otherwise disclose any part of the beta app to any third party without prior written permission.</p>

                        <p><strong>2. Nature of the Beta</strong><br>
                        The beta app is pre-release software provided "as is." It may contain bugs, errors, or incomplete features. It is not suitable for production or business-critical use during the beta period.</p>

                        <p><strong>3. Feedback</strong><br>
                        You agree to provide honest feedback about your experience, including bugs and feature suggestions. Any feedback you submit becomes the property of My Pottery Studio and may be used to improve the app without compensation to you.</p>

                        <p><strong>4. No Redistribution</strong><br>
                        You may not copy, distribute, reverse engineer, or create derivative works from the beta app or any related materials.</p>

                        <p><strong>5. Data Collection</strong><br>
                        During the beta period, the app may collect usage data and crash reports to help identify issues. No personal pottery or financial data will be shared with third parties.</p>

                        <p><strong>6. Account</strong><br>
                        You are responsible for keeping your beta account credentials secure. Your access may be revoked at any time if these terms are violated.</p>

                        <p><strong>7. No Warranty</strong><br>
                        My Pottery Studio makes no warranties about the beta app's fitness for any purpose. Participation is voluntary and at your own risk.</p>

                        <p><strong>8. Termination</strong><br>
                        Either party may end participation at any time. Upon termination you agree to delete any copies of the beta app in your possession.</p>
                    </div>
                    <div class="beta-agreement-fade" id="agreementFade"></div>
                </div>
                <button type="button" class="beta-agreement-expand" id="expandAgreement">
                    Read full agreement ↓
                </button>
            </div>

            <!-- EULA -->
            <div class="beta-form__group">
                <label>End User License Agreement (EULA)</label>
                <div class="beta-agreement-box" id="eulaBox">
                    <div class="beta-agreement-text">
                        <?php
                        // Inline summary — full text at /beta/eula.php
                        $siteName = setting('site_name', 'My Pottery Studio');
                        ?>
                        <p><strong><?= e($siteName) ?> Beta EULA — Summary</strong></p>
                        <p>This is a legal agreement between you and the Developer. By agreeing you confirm that you:</p>
                        <ul style="padding-left:1.1rem;margin:.5rem 0">
                            <li>Receive a limited, non-transferable license to use the App for testing only.</li>
                            <li>Will not copy, reverse engineer, redistribute, or commercially use the App.</li>
                            <li>Will keep all App details, features, and design strictly confidential.</li>
                            <li>Grant the Developer a royalty-free license to use any Feedback you submit.</li>
                            <li>Acknowledge the App is provided "as is" with no warranty.</li>
                            <li>Accept that the Developer is not liable for data loss or damages.</li>
                            <li>Must delete the App if your access is terminated or the beta ends.</li>
                        </ul>
                        <p><a href="/beta/eula.php" target="_blank">Read the full EULA ↗</a></p>
                    </div>
                    <div class="beta-agreement-fade" id="eulaFade"></div>
                </div>
                <button type="button" class="beta-agreement-expand" id="expandEula">
                    Read full summary ↓
                </button>
            </div>

            <div class="beta-form__group">
                <label class="beta-checkbox-label">
                    <input type="checkbox" name="eula" value="1" required
                           <?= !empty($_POST['eula']) ? 'checked' : '' ?>>
                    <span>I have read and agree to the <strong><a href="/beta/eula.php" target="_blank">End User License Agreement</a></strong></span>
                </label>
            </div>

            <div class="beta-form__group">
                <label class="beta-checkbox-label">
                    <input type="checkbox" name="agreement" value="1" required
                           <?= !empty($_POST['agreement']) ? 'checked' : '' ?>>
                    <span>I have read and agree to the <strong>Beta Test Agreement</strong> and <a href="/beta/privacy.php" target="_blank">Privacy Policy</a></span>
                </label>
            </div>

            <button type="submit" class="beta-btn beta-btn--primary">Create Account</button>
        </form>

        <?php endif; ?>

        <p class="beta-auth-card__footer">
            Already have an account? <a href="/beta/login.php">Sign in</a>
        </p>
    </div>
</div>

<script>
(function () {
    function makeToggle(boxId, fadeId, btnId, collapseLabel, expandLabel) {
        const box  = document.getElementById(boxId);
        const fade = document.getElementById(fadeId);
        const btn  = document.getElementById(btnId);
        if (!box || !btn) return;
        let expanded = false;
        btn.addEventListener('click', function () {
            expanded = !expanded;
            box.classList.toggle('beta-agreement-box--expanded', expanded);
            if (fade) fade.style.display = expanded ? 'none' : '';
            btn.textContent = expanded ? collapseLabel : expandLabel;
        });
    }

    makeToggle('agreementBox', 'agreementFade', 'expandAgreement', 'Collapse ↑', 'Read full agreement ↓');
    makeToggle('eulaBox',      'eulaFade',      'expandEula',      'Collapse ↑', 'Read full summary ↓');
})();
</script>
</body>
</html>
