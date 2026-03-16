<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — My Pottery Studio Beta</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/beta/css/beta.css">
</head>
<body class="beta-auth-body beta-policy-body">

<div class="beta-auth-wrap beta-auth-wrap--wide">
    <div class="beta-auth-card beta-policy-card">

        <div class="beta-auth-card__logo">
            <span class="beta-badge">BETA</span>
            <h1>My Pottery Studio</h1>
            <p>Privacy Policy — Beta Program</p>
        </div>

        <div class="beta-policy-meta">
            Last updated: <?= date('F j, Y') ?>
        </div>

        <div class="beta-policy-content">

            <p>Your privacy matters. This policy explains exactly what information is collected when you sign up for the My Pottery Studio beta program and how it is used. It is intentionally short and plain-language.</p>

            <h2>What we collect</h2>
            <p>When you register for the beta program we collect:</p>
            <ul>
                <li>Your <strong>name</strong> — used to address you personally in emails.</li>
                <li>Your <strong>email address</strong> — used only to communicate with you about the beta test (see below).</li>
                <li>Your <strong>platform preference</strong> (Android, iOS, etc.) — used to send you platform-relevant updates.</li>
                <li>Any <strong>bug reports or feature requests</strong> you voluntarily submit through the portal.</li>
            </ul>

            <h2>How your email is used</h2>
            <p>Your email address will <strong>only ever be used to send you messages directly related to the beta test</strong>, including:</p>
            <ul>
                <li>New beta build notifications and download links.</li>
                <li>Important announcements about the beta program (e.g. schedule changes, end of beta).</li>
                <li>Replies or follow-ups related to feedback you have submitted.</li>
            </ul>
            <p>We will <strong>never</strong>:</p>
            <ul>
                <li>Send you marketing, promotional, or newsletter emails unrelated to the beta test.</li>
                <li>Share, sell, rent, or disclose your email address to any third party.</li>
                <li>Add you to any other mailing list without your explicit consent.</li>
            </ul>

            <h2>Data storage</h2>
            <p>Your information is stored securely in a private database hosted on Bluehost. Passwords are hashed using bcrypt and are never stored or visible in plain text. Only the app owner has access to the database.</p>

            <h2>Data retention</h2>
            <p>Your account and associated data will be deleted within 30 days of the beta program ending, or immediately upon your request.</p>

            <h2>Your rights</h2>
            <p>You can request deletion of your account and all associated data at any time by emailing <a href="mailto:<?= e(setting('contact_email', 'hello@mypotterystudio.com')) ?>"><?= e(setting('contact_email', 'hello@mypotterystudio.com')) ?></a>. We will action all deletion requests within 7 days.</p>

            <h2>Cookies &amp; tracking</h2>
            <p>The beta portal uses a single session cookie to keep you logged in. No analytics, advertising trackers, or third-party cookies are used.</p>

            <h2>Contact</h2>
            <p>Questions about this policy? Email <a href="mailto:<?= e(setting('contact_email', 'hello@mypotterystudio.com')) ?>"><?= e(setting('contact_email', 'hello@mypotterystudio.com')) ?></a>.</p>

        </div>

        <p class="beta-auth-card__footer">
            <a href="/beta/register.php">← Back to sign up</a>
            &nbsp;·&nbsp;
            <a href="/beta/login.php">Sign in</a>
        </p>
    </div>
</div>

</body>
</html>
