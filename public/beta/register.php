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

    if ($password !== $confirm) {
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

<div class="beta-auth-wrap">
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
            <button type="submit" class="beta-btn beta-btn--primary">Create Account</button>
        </form>

        <?php endif; ?>

        <p class="beta-auth-card__footer">
            Already have an account? <a href="/beta/login.php">Sign in</a>
        </p>
    </div>
</div>

</body>
</html>
