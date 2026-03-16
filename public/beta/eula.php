<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$siteName = setting('site_name', 'My Pottery Studio');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EULA — My Pottery Studio Beta</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/beta/css/beta.css">
</head>
<body class="beta-auth-body beta-policy-body">

<div class="beta-auth-wrap beta-auth-wrap--wide">
    <div class="beta-auth-card beta-policy-card">

        <div class="beta-auth-card__logo">
            <span class="beta-badge">BETA</span>
            <h1><?= e($siteName) ?></h1>
            <p>End User License Agreement — Beta Program</p>
        </div>

        <div class="beta-policy-meta">
            Last updated: <?= date('F j, Y') ?> &nbsp;·&nbsp; Version 1.0
        </div>

        <div class="beta-policy-content">

            <p>This End User License Agreement ("EULA") is a legal agreement between you ("Tester," "you") and the developer of My Pottery Studio ("Developer," "we," "us"). By installing, accessing, or using the My Pottery Studio beta application ("App"), you agree to be bound by the terms of this EULA. If you do not agree, do not install or use the App.</p>

            <h2>1. Grant of License</h2>
            <p>Subject to your compliance with this EULA, we grant you a limited, non-exclusive, non-transferable, revocable license to install and use the App solely for the purpose of testing and evaluating it during the beta period ("Beta Period"). This license does not grant you any rights to the App other than those expressly stated here.</p>

            <h2>2. Beta Status &amp; No Warranty</h2>
            <p>The App is pre-release, beta software. It is provided <strong>"as is" and "as available" without warranty of any kind</strong>, express or implied, including but not limited to warranties of merchantability, fitness for a particular purpose, or non-infringement. The App may contain bugs, errors, missing features, or cause unexpected behaviour including data loss. You use the App entirely at your own risk.</p>

            <h2>3. Restrictions</h2>
            <p>You agree that you will not, and will not permit any third party to:</p>
            <ul>
                <li>Copy, reproduce, modify, translate, or create derivative works of the App or any part of it.</li>
                <li>Reverse engineer, disassemble, decompile, or attempt to derive the source code of the App.</li>
                <li>Distribute, sublicense, rent, lease, sell, transfer, or otherwise make the App available to any third party.</li>
                <li>Use the App for any commercial purpose or for any public demonstration.</li>
                <li>Remove, alter, or obscure any copyright, trademark, or other proprietary notices within the App.</li>
                <li>Use the App in any way that violates applicable law or regulation.</li>
                <li>Attempt to circumvent any security feature, access control, or technical limitation of the App.</li>
            </ul>

            <h2>4. Confidentiality</h2>
            <p>The App, its features, design, functionality, and any information disclosed to you in connection with the beta program are confidential information ("Confidential Information"). You agree to:</p>
            <ul>
                <li>Keep all Confidential Information strictly confidential.</li>
                <li>Not disclose Confidential Information to any third party without prior written consent from the Developer.</li>
                <li>Not publish, post, stream, or share screenshots, screen recordings, or descriptions of the App on any public platform, social media, or otherwise.</li>
                <li>Use the same degree of care to protect Confidential Information as you use to protect your own confidential information, but no less than reasonable care.</li>
            </ul>

            <h2>5. Intellectual Property</h2>
            <p>The App and all copies thereof are proprietary to the Developer and title thereto remains in the Developer. All applicable rights in all copyrights, trademarks, trade secrets, patents, and other intellectual property rights in or associated with the App are and will remain the property of the Developer. This EULA does not convey to you any interest in or to the App, but only a limited right of use that is revocable in accordance with the terms of this EULA.</p>

            <h2>6. Feedback &amp; Submissions</h2>
            <p>You are encouraged to submit bug reports, feature requests, and other feedback ("Feedback") through the beta portal. By submitting Feedback you:</p>
            <ul>
                <li>Grant the Developer a perpetual, irrevocable, worldwide, royalty-free license to use, reproduce, modify, and incorporate your Feedback into the App or related products without restriction.</li>
                <li>Acknowledge that the Developer has no obligation to act on, credit, or compensate you for any Feedback.</li>
                <li>Confirm that your Feedback does not contain information that is confidential to a third party or that you are not authorised to disclose.</li>
            </ul>

            <h2>7. Data Collection During Testing</h2>
            <p>During the Beta Period the App may automatically collect diagnostic data including crash reports, performance metrics, and anonymised usage patterns. This data is used solely to identify and fix bugs and improve the App. It will not be sold or shared with third parties. For details on how your personal information (including your email address) is handled, please refer to our <a href="/beta/privacy.php" target="_blank">Privacy Policy</a>.</p>

            <h2>8. Updates &amp; Changes</h2>
            <p>The Developer reserves the right to update, modify, or discontinue the App or any part of it at any time without notice. We may release new versions of the App during the Beta Period and you agree to install updates when directed to do so. Continued use of the App following an update constitutes acceptance of any revised terms.</p>

            <h2>9. Term &amp; Termination</h2>
            <p>This EULA is effective from the date you agree to it and continues until the Beta Period ends or until terminated, whichever comes first.</p>
            <p>The Developer may terminate your access immediately and without notice if you breach any term of this EULA. You may terminate your participation at any time by deleting the App and notifying the Developer.</p>
            <p>Upon termination for any reason you must immediately uninstall and delete all copies of the App in your possession. Sections 4 (Confidentiality), 5 (Intellectual Property), 6 (Feedback), 10 (Limitation of Liability), and 11 (General) survive termination.</p>

            <h2>10. Limitation of Liability</h2>
            <p>To the maximum extent permitted by applicable law, in no event shall the Developer be liable for any indirect, incidental, special, consequential, or punitive damages — including but not limited to loss of data, loss of profits, or business interruption — arising out of or in connection with your use of or inability to use the App, even if the Developer has been advised of the possibility of such damages.</p>
            <p>The Developer's total aggregate liability for any claims arising under this EULA shall not exceed the amount you paid for the App (which, being a free beta, is nil).</p>

            <h2>11. General</h2>
            <ul>
                <li><strong>Entire Agreement.</strong> This EULA, together with the Beta Test Agreement and Privacy Policy, constitutes the entire agreement between you and the Developer regarding the App and supersedes all prior agreements.</li>
                <li><strong>Severability.</strong> If any provision of this EULA is found to be unenforceable, the remaining provisions will continue in full force and effect.</li>
                <li><strong>No Waiver.</strong> Failure to enforce any provision of this EULA shall not constitute a waiver of the Developer's right to enforce it in the future.</li>
                <li><strong>Amendments.</strong> The Developer may update this EULA at any time. Continued use of the App following notice of changes constitutes acceptance of the updated terms.</li>
                <li><strong>Governing Law.</strong> This EULA is governed by the laws of the jurisdiction in which the Developer operates, without regard to conflict-of-law principles.</li>
            </ul>

            <p>By checking the box on the registration form you confirm that you have read, understood, and agree to be bound by this EULA.</p>

        </div>

        <p class="beta-auth-card__footer">
            <a href="/beta/register.php">← Back to sign up</a>
            &nbsp;·&nbsp;
            <a href="/beta/privacy.php">Privacy Policy</a>
            &nbsp;·&nbsp;
            <a href="/beta/login.php">Sign in</a>
        </p>
    </div>
</div>

</body>
</html>
