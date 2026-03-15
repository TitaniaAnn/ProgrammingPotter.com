<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/BetaAuth.php';

if (BetaAuth::isLoggedIn()) {
    redirect('/beta/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (BetaAuth::login($email, $password)) {
        redirect('/beta/dashboard.php');
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beta Login — My Pottery Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/beta/css/beta.css">
</head>
<body class="beta-auth-body">

<div class="beta-auth-wrap">
    <div class="beta-auth-card">
        <div class="beta-auth-card__logo">
            <span class="beta-badge">BETA</span>
            <h1>My Pottery Studio</h1>
            <p>Beta Tester Portal</p>
        </div>

        <?php if ($error): ?>
            <div class="beta-alert beta-alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="beta-form">
            <div class="beta-form__group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= e($_POST['email'] ?? '') ?>"
                       placeholder="you@example.com">
            </div>
            <div class="beta-form__group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       placeholder="••••••••">
            </div>
            <button type="submit" class="beta-btn beta-btn--primary">Sign In</button>
        </form>

        <p class="beta-auth-card__footer">
            Don't have an account? <a href="/beta/register.php">Request beta access</a>
        </p>
    </div>
</div>

</body>
</html>
