<?php
require_once __DIR__ . '/../includes/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — My Pottery Studio</title>
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Caveat:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/favicon-512.png">
    <link rel="apple-touch-icon" href="/favicon-512.png">
</head>
<body>
<?php include __DIR__ . '/../templates/nav.php'; ?>

<div class="page-header">
    <div class="container">
        <h1 class="page-header__title">Privacy Policy</h1>
        <p class="page-header__sub">My Pottery Studio</p>
    </div>
</div>

<section class="section">
    <div class="container prose">

        <p class="prose__meta">Last updated: March 2026</p>

        <p>This Privacy Policy describes how My Pottery Studio ("the App") collects, uses, and handles your information when you use our mobile application.</p>

        <h2>1. Information We Collect</h2>
        <p>The App collects only what you explicitly enter:</p>
        <ul>
            <li><strong>Studio &amp; settings data</strong> — studio name, hourly rate, theme preferences, and feature toggles, stored locally on your device.</li>
            <li><strong>Pottery inventory</strong> — piece names, types, stages, notes, clay and glaze details, and photos you choose to attach.</li>
            <li><strong>Client information</strong> — names, email addresses, and phone numbers you enter when creating client profiles.</li>
            <li><strong>Sales &amp; commission data</strong> — sale prices, dates, and links to pieces and clients.</li>
            <li><strong>Kiln &amp; materials data</strong> — firing schedules, material names, and purchase records.</li>
            <li><strong>AI caption drafts</strong> — if you use the optional AI caption feature, the piece name, type, stage, clays, and glazes are sent to the AI provider you have configured (Anthropic Claude or Google Gemini). No personal or client data is included.</li>
        </ul>

        <h2>2. How We Use Your Information</h2>
        <ul>
            <li>To let you track, manage, and document your pottery work.</li>
            <li>To generate AI-assisted social media captions when you request them.</li>
            <li>To calculate pricing and produce sales reports within the App.</li>
            <li>To restore your data from a local backup file you have created.</li>
        </ul>

        <h2>3. Data Storage &amp; Security</h2>
        <p>All your data — pieces, clients, sales, photos, and settings — is stored in a local SQLite database on your device. Nothing is uploaded to our servers. Only when you explicitly trigger an action that contacts an external service (see Section 4) does any data leave your device.</p>

        <h2>4. Third-Party Services</h2>
        <ul>
            <li><strong>Anthropic (Claude)</strong> — If you enter an Anthropic API key and request an AI caption, piece details (name, type, stage, clays, glazes) are sent to Anthropic's API. This is subject to <a href="https://www.anthropic.com/privacy" target="_blank" rel="noopener">Anthropic's Privacy Policy</a>.</li>
            <li><strong>Google (Gemini)</strong> — If you enter a Gemini API key and request an AI caption, piece details are sent to Google's Generative Language API. This is subject to <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Google's Privacy Policy</a>.</li>
            <li><strong>Camera &amp; Photo Library</strong> — The App requests access only when you choose to attach a photo to a piece. Images are stored locally and never uploaded.</li>
        </ul>
        <p>No analytics, advertising, or crash-reporting SDKs are included in the App.</p>

        <h2>5. Data Sharing</h2>
        <p>We do not sell, trade, or share your personal data with any third party, except as described in Section 4 when you voluntarily use the optional AI feature. Client names, emails, and phone numbers are never sent outside your device.</p>

        <h2>6. Your Rights &amp; Choices</h2>
        <ul>
            <li><strong>Access &amp; correction</strong> — You can view and edit all data directly within the App.</li>
            <li><strong>Deletion</strong> — Deleting a record in the App removes it. Uninstalling the App removes all data from your device.</li>
            <li><strong>AI opt-out</strong> — Leave the AI API key fields blank at any time; captions will fall back to local templates with no data sent externally.</li>
            <li><strong>Backup control</strong> — Local backups are created only when you tap "Backup to This Device." The resulting file is stored wherever you choose on your device.</li>
        </ul>

        <h2>7. Children's Privacy</h2>
        <p>The App is not directed at children under 13. We do not knowingly collect personal information from children.</p>

        <h2>8. Changes to This Policy</h2>
        <p>We may update this Privacy Policy from time to time. Updates will be reflected in the "Last updated" date above and in the version of the App on the relevant app store.</p>

        <h2>9. Contact</h2>
        <p>Questions about this Privacy Policy? <a href="/contact">Contact us here</a>.</p>

    </div>
</section>

<?php include __DIR__ . '/../templates/footer.php'; ?>
<script src="/js/main.js"></script>
</body>
</html>
