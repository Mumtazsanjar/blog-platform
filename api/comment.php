<?php
/**
 * API: Submit a comment (multipart form data / AJAX)
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

// CSRF
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh the page.']);
    exit;
}

$postId  = (int)($_POST['post_id'] ?? 0);
$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$content = trim($_POST['content'] ?? '');

// Validate
$errors = [];
if (!$postId)           $errors[] = 'Invalid post.';
if (empty($name))       $errors[] = 'Name is required.';
if (strlen($name) > 80) $errors[] = 'Name is too long.';
if (empty($email))      $errors[] = 'Email is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
if (empty($content))    $errors[] = 'Comment cannot be empty.';
if (strlen($content) < 5)    $errors[] = 'Comment is too short.';
if (strlen($content) > 2000) $errors[] = 'Comment is too long (max 2000 characters).';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
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

// Check if comments are allowed
if (getSetting('allow_comments', '1') !== '1') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Comments are currently disabled.']);
    exit;
}

// Rate limiting: max 5 comments per IP per hour
$ip = getUserIp();
$rateStmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE ip_address = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$rateStmt->execute([$ip]);
if ((int)$rateStmt->fetchColumn() >= 5) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many comments. Please wait before posting again.']);
    exit;
}

// Insert (not auto-approved)
$stmt = $pdo->prepare("INSERT INTO comments (post_id, name, email, content, ip_address, approved) VALUES (?,?,?,?,?,0)");
$stmt->execute([$postId, $name, $email, $content, $ip]);

echo json_encode([
    'success' => true,
    'message' => 'Thank you! Your comment has been submitted and is awaiting approval.',
]);
