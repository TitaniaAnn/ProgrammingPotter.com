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
    $platform = in_array($_POST['platform'] ?? '', ['android','ios','both','other']) ? $_POST['platform'] : 'other';
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
                    <?php foreach (['android' => 'Android', 'ios' => 'iOS', 'both' => 'Both', 'other' => 'Other'] as $val => $label): ?>
                    <label class="beta-toggle-option">
                        <input type="radio" name="platform" value="<?= $val ?>"
                               <?= (($_POST['platform'] ?? 'android') === $val) ? 'checked' : '' ?>>
                        <span><?= $label ?></span>
                    </label>
                    <?php endforeach; ?>
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
