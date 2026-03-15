<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/BetaAuth.php';
require_once ROOT_PATH . '/includes/GitHubAPI.php';

BetaAuth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/beta/dashboard.php');
}

$user  = BetaAuth::getUser();
$type  = in_array($_POST['type'] ?? '', ['bug', 'feature']) ? $_POST['type'] : 'bug';
$title = trim($_POST['title'] ?? '');
$body  = trim($_POST['body'] ?? '');

if (!$title || !$body) {
    flash('error', 'Title and description are required.');
    redirect('/beta/dashboard.php#feedback');
}

// Build the GitHub issue body with tester credit
$ghBody = $body . "\n\n---\n*Submitted by beta tester: " . $user['name'] . " (" . $user['email'] . ")*";

$ghLabels = $type === 'bug' ? ['bug'] : ['enhancement'];

$issue     = [];
$issueNum  = null;
$issueUrl  = null;

if (BETA_GITHUB_REPO && BETA_GITHUB_TOKEN) {
    $issue    = GitHubAPI::createIssue(BETA_GITHUB_REPO, BETA_GITHUB_TOKEN, $title, $ghBody, $ghLabels);
    $issueNum = $issue['number'] ?? null;
    $issueUrl = $issue['html_url'] ?? null;
}

// Always store locally too
Database::insert('beta_feedback', [
    'user_id'             => $user['id'],
    'type'                => $type,
    'title'               => $title,
    'body'                => $body,
    'github_issue_number' => $issueNum,
    'github_issue_url'    => $issueUrl,
    'status'              => 'open',
]);

if ($issueUrl) {
    flash('success', 'Feedback submitted and tracked on GitHub! 🎉');
} else {
    flash('success', 'Feedback submitted — thank you!');
}

redirect('/beta/dashboard.php#feedback');
