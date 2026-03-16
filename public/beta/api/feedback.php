<?php
// Beta API — returns community feedback submissions with vote counts
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/BetaAuth.php';

header('Content-Type: application/json');

if (!BetaAuth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = BetaAuth::getUser()['id'];

$params     = [];
$conditions = [];

$type = $_GET['type'] ?? 'all';
if (in_array($type, ['bug', 'feature'])) {
    $conditions[] = 'f.type = ?';
    $params[]     = $type;
}

$status = $_GET['status'] ?? 'all';
if (in_array($status, ['open', 'in_progress', 'closed'])) {
    $conditions[] = 'f.status = ?';
    $params[]     = $status;
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$sort = $_GET['sort'] ?? 'votes';
$orderBy = $sort === 'newest' ? 'f.created_at DESC' : '(upvotes - downvotes) DESC, f.created_at DESC';

$rows = Database::fetchAll(
    "SELECT
        f.id, f.type, f.title, f.body, f.status, f.created_at,
        f.github_issue_number, f.github_issue_url,
        u.name AS user_name,
        COALESCE(SUM(CASE WHEN v.vote =  1 THEN 1 ELSE 0 END), 0) AS upvotes,
        COALESCE(SUM(CASE WHEN v.vote = -1 THEN 1 ELSE 0 END), 0) AS downvotes,
        MAX(CASE WHEN v.user_id = ? THEN v.vote ELSE NULL END)     AS my_vote
     FROM beta_feedback f
     JOIN beta_users u ON u.id = f.user_id
     LEFT JOIN beta_votes v ON v.feedback_id = f.id
     $where
     GROUP BY f.id
     ORDER BY $orderBy",
    array_merge([$userId], $params)
);

// Cast numeric fields
foreach ($rows as &$r) {
    $r['upvotes']   = (int)$r['upvotes'];
    $r['downvotes'] = (int)$r['downvotes'];
    $r['my_vote']   = $r['my_vote'] === null ? 0 : (int)$r['my_vote'];
}

echo json_encode(array_values($rows));
