<?php
// Beta API — proxies GitHub issues so the token stays server-side
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/BetaAuth.php';
require_once ROOT_PATH . '/includes/GitHubAPI.php';

header('Content-Type: application/json');

if (!BetaAuth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$state  = in_array($_GET['state'] ?? 'all', ['open', 'closed', 'all']) ? $_GET['state'] : 'all';
$issues = GitHubAPI::getIssues(BETA_GITHUB_REPO, $state, BETA_GITHUB_TOKEN);

// Return only the fields the frontend needs
$slim = array_map(fn($i) => [
    'number'     => $i['number'],
    'title'      => $i['title'],
    'state'      => $i['state'],
    'html_url'   => $i['html_url'],
    'labels'     => array_map(fn($l) => ['name' => $l['name'], 'color' => $l['color']], $i['labels'] ?? []),
    'created_at' => $i['created_at'],
    'updated_at' => $i['updated_at'],
    'comments'   => $i['comments'],
    'body'       => mb_strimwidth($i['body'] ?? '', 0, 200, '…'),
], $issues);

echo json_encode($slim);
