<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/beta/email.php');
}

$audience = $_POST['audience'] ?? 'all';
$subject  = trim($_POST['subject'] ?? '');
$body     = trim($_POST['body'] ?? '');

if (!$subject || !$body) {
    flash('error', 'Subject and message are required.');
    redirect('/admin/beta/email.php');
}

$where = match($audience) {
    'android' => "WHERE approved=1 AND platform IN ('android','both')",
    'ios'     => "WHERE approved=1 AND platform IN ('ios','both')",
    default   => "WHERE approved=1",
};

$testers = Database::fetchAll("SELECT name, email FROM beta_users $where");

if (!$testers) {
    flash('error', 'No testers found for that audience.');
    redirect('/admin/beta/email.php');
}

$sent = 0;
foreach ($testers as $tester) {
    Mailer::sendBetaEmail($tester['email'], $tester['name'], $subject, $body);
    $sent++;
}

$label = match($audience) {
    'android' => 'Android testers',
    'ios'     => 'iOS testers',
    default   => 'all testers',
};

flash('success', "Email sent to {$sent} {$label}.");
redirect('/admin/beta/email.php');
