<?php
// Beta API — cast or retract a vote on a feedback submission
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/BetaAuth.php';

header('Content-Type: application/json');

if (!BetaAuth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$feedbackId = (int)($body['feedback_id'] ?? 0);
$vote       = (int)($body['vote'] ?? 0); // 1 or -1

if (!$feedbackId || !in_array($vote, [1, -1])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$userId = BetaAuth::getUser()['id'];

// Check item exists
$item = Database::fetchOne("SELECT id FROM beta_feedback WHERE id = ?", [$feedbackId]);
if (!$item) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

// Get existing vote
$existing = Database::fetchOne(
    "SELECT vote FROM beta_votes WHERE feedback_id = ? AND user_id = ?",
    [$feedbackId, $userId]
);

if ($existing) {
    if ((int)$existing['vote'] === $vote) {
        // Same vote again — retract it
        Database::delete('beta_votes', 'feedback_id = :fid AND user_id = :uid', [
            'fid' => $feedbackId, 'uid' => $userId,
        ]);
    } else {
        // Switched vote — update
        Database::update('beta_votes', ['vote' => $vote],
            'feedback_id = :fid AND user_id = :uid',
            ['fid' => $feedbackId, 'uid' => $userId]
        );
    }
} else {
    // New vote
    Database::insert('beta_votes', [
        'feedback_id' => $feedbackId,
        'user_id'     => $userId,
        'vote'        => $vote,
    ]);
}

// Return updated counts and this user's current vote
$counts = Database::fetchOne(
    "SELECT
        COALESCE(SUM(CASE WHEN vote =  1 THEN 1 ELSE 0 END), 0) AS upvotes,
        COALESCE(SUM(CASE WHEN vote = -1 THEN 1 ELSE 0 END), 0) AS downvotes,
        MAX(CASE WHEN user_id = ? THEN vote ELSE NULL END)       AS my_vote
     FROM beta_votes WHERE feedback_id = ?",
    [$userId, $feedbackId]
);

echo json_encode([
    'upvotes'   => (int)$counts['upvotes'],
    'downvotes' => (int)$counts['downvotes'],
    'my_vote'   => $counts['my_vote'] === null ? 0 : (int)$counts['my_vote'],
]);
