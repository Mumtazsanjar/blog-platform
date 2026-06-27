<?php
/**
 * API: Like / Unlike a post
 * POST JSON: { "post_id": 5 }
 */
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$body   = json_decode(file_get_contents('php://input'), true);
$postId = (int)($body['post_id'] ?? 0);

if (!$postId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid post ID.']);
    exit;
}

$pdo = getDB();

// Check post exists
$check = $pdo->prepare("SELECT id FROM posts WHERE id=? AND status='published'");
$check->execute([$postId]);
if (!$check->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Post not found.']);
    exit;
}

$ip = getUserIp();

// Check if already liked
$existsStmt = $pdo->prepare("SELECT id FROM likes WHERE post_id=? AND ip_address=?");
$existsStmt->execute([$postId, $ip]);
$exists = $existsStmt->fetch();

if ($exists) {
    // Unlike
    $pdo->prepare("DELETE FROM likes WHERE post_id=? AND ip_address=?")->execute([$postId, $ip]);
    $liked = false;
} else {
    // Like
    $pdo->prepare("INSERT INTO likes (post_id, ip_address) VALUES (?,?)")->execute([$postId, $ip]);
    $liked = true;
}

$likeCount = getLikeCount($postId);

echo json_encode([
    'success' => true,
    'liked'   => $liked,
    'likes'   => $likeCount,
]);
