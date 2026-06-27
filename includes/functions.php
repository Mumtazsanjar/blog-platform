<?php
/**
 * Helper / utility functions
 */

require_once __DIR__ . '/db.php';

// ── Slug ──────────────────────────────────────────────────────────────────────

function createSlug(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $text);
    $text = preg_replace('/[\s_]+/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

function uniqueSlug(string $title, string $table, int $excludeId = 0): string {
    $pdo  = getDB();
    $slug = createSlug($title);
    $base = $slug;
    $i    = 1;
    while (true) {
        $sql  = "SELECT COUNT(*) FROM `$table` WHERE slug = ? AND id != ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$slug, $excludeId]);
        if ((int)$stmt->fetchColumn() === 0) break;
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

// ── Settings ──────────────────────────────────────────────────────────────────

function getSetting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        $pdo   = getDB();
        $rows  = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
        $cache = array_column($rows, 'setting_value', 'setting_key');
    }
    return $cache[$key] ?? $default;
}

// ── Posts ─────────────────────────────────────────────────────────────────────

function getPosts(int $page = 1, int $perPage = 6, ?int $categoryId = null, string $status = 'published'): array {
    $pdo    = getDB();
    $offset = ($page - 1) * $perPage;
    $params = [$status];
    $where  = 'p.status = ?';

    if ($categoryId !== null) {
        $where   .= ' AND p.category_id = ?';
        $params[] = $categoryId;
    }

    $sql = "
        SELECT p.*, c.name AS category_name, c.slug AS category_slug,
               u.display_name AS author_name,
               (SELECT COUNT(*) FROM likes   l WHERE l.post_id = p.id) AS like_count,
               (SELECT COUNT(*) FROM comments co WHERE co.post_id = p.id AND co.approved = 1) AS comment_count
        FROM   posts p
        LEFT   JOIN categories c ON c.id = p.category_id
        LEFT   JOIN users u       ON u.id = p.author_id
        WHERE  $where
        ORDER  BY p.created_at DESC
        LIMIT  ? OFFSET ?
    ";
    $params[] = $perPage;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function countPosts(?int $categoryId = null, string $status = 'published'): int {
    $pdo    = getDB();
    $params = [$status];
    $where  = 'status = ?';
    if ($categoryId !== null) {
        $where   .= ' AND category_id = ?';
        $params[] = $categoryId;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE $where");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function getPostBySlug(string $slug): ?array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug,
               u.display_name AS author_name,
               (SELECT COUNT(*) FROM likes   l  WHERE l.post_id  = p.id) AS like_count,
               (SELECT COUNT(*) FROM comments co WHERE co.post_id = p.id AND co.approved = 1) AS comment_count
        FROM   posts p
        LEFT   JOIN categories c ON c.id = p.category_id
        LEFT   JOIN users u       ON u.id = p.author_id
        WHERE  p.slug = ? AND p.status = 'published'
        LIMIT  1
    ");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function getPostById(int $id): ?array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug,
               u.display_name AS author_name
        FROM   posts p
        LEFT   JOIN categories c ON c.id = p.category_id
        LEFT   JOIN users u       ON u.id = p.author_id
        WHERE  p.id = ?
        LIMIT  1
    ");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function incrementViews(int $postId): void {
    $pdo  = getDB();
    $stmt = $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
    $stmt->execute([$postId]);
}

function getFeaturedPosts(int $limit = 3): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM   posts p
        LEFT   JOIN categories c ON c.id = p.category_id
        WHERE  p.status = 'published' AND p.featured = 1
        ORDER  BY p.created_at DESC
        LIMIT  ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getRelatedPosts(int $postId, ?int $categoryId, int $limit = 3): array {
    $pdo = getDB();
    if ($categoryId) {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM   posts p
            LEFT   JOIN categories c ON c.id = p.category_id
            WHERE  p.status = 'published' AND p.category_id = ? AND p.id != ?
            ORDER  BY p.created_at DESC LIMIT ?
        ");
        $stmt->execute([$categoryId, $postId, $limit]);
    } else {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM   posts p
            LEFT   JOIN categories c ON c.id = p.category_id
            WHERE  p.status = 'published' AND p.id != ?
            ORDER  BY RAND() LIMIT ?
        ");
        $stmt->execute([$postId, $limit]);
    }
    return $stmt->fetchAll();
}

// ── Categories ────────────────────────────────────────────────────────────────

function getCategories(): array {
    $pdo  = getDB();
    $stmt = $pdo->query("
        SELECT c.*, COUNT(p.id) AS post_count
        FROM   categories c
        LEFT   JOIN posts p ON p.category_id = c.id AND p.status = 'published'
        GROUP  BY c.id
        ORDER  BY c.name
    ");
    return $stmt->fetchAll();
}

function getCategoryBySlug(string $slug): ?array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

// ── Tags ──────────────────────────────────────────────────────────────────────

function getPostTags(int $postId): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT t.* FROM tags t
        INNER JOIN post_tags pt ON pt.tag_id = t.id
        WHERE pt.post_id = ?
    ");
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

// ── Comments ──────────────────────────────────────────────────────────────────

function getComments(int $postId): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM comments WHERE post_id = ? AND approved = 1 ORDER BY created_at ASC");
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

// ── Search ────────────────────────────────────────────────────────────────────

function searchPosts(string $query, int $page = 1, int $perPage = 6): array {
    $pdo    = getDB();
    $offset = ($page - 1) * $perPage;
    $q      = '%' . $query . '%';
    $stmt   = $pdo->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug,
               u.display_name AS author_name
        FROM   posts p
        LEFT   JOIN categories c ON c.id = p.category_id
        LEFT   JOIN users u       ON u.id = p.author_id
        WHERE  p.status = 'published' AND (p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)
        ORDER  BY p.created_at DESC
        LIMIT  ? OFFSET ?
    ");
    $stmt->execute([$q, $q, $q, $perPage, $offset]);
    return $stmt->fetchAll();
}

function countSearchPosts(string $query): int {
    $pdo  = getDB();
    $q    = '%' . $query . '%';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE status = 'published' AND (title LIKE ? OR content LIKE ? OR excerpt LIKE ?)");
    $stmt->execute([$q, $q, $q]);
    return (int)$stmt->fetchColumn();
}

// ── Likes ─────────────────────────────────────────────────────────────────────

function getUserIp(): string {
    return $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['HTTP_CLIENT_IP']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '0.0.0.0';
}

function hasLiked(int $postId): bool {
    $pdo  = getDB();
    $ip   = getUserIp();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ? AND ip_address = ?");
    $stmt->execute([$postId, $ip]);
    return (int)$stmt->fetchColumn() > 0;
}

function getLikeCount(int $postId): int {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $stmt->execute([$postId]);
    return (int)$stmt->fetchColumn();
}

// ── CSRF ──────────────────────────────────────────────────────────────────────

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

// ── Reading time ──────────────────────────────────────────────────────────────

function readingTime(string $content): int {
    $wordCount = str_word_count(strip_tags($content));
    return max(1, (int)ceil($wordCount / 200));
}

// ── Pagination ────────────────────────────────────────────────────────────────

function paginationLinks(int $currentPage, int $totalPages, string $baseUrl): string {
    if ($totalPages <= 1) return '';
    $html = '<nav class="pagination" aria-label="Pagination"><ul>';

    if ($currentPage > 1) {
        $html .= '<li><a href="' . htmlspecialchars($baseUrl . ($currentPage - 1)) . '" aria-label="Previous">&laquo; Prev</a></li>';
    }

    $start = max(1, $currentPage - 2);
    $end   = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $html .= '<li><a href="' . htmlspecialchars($baseUrl . '1') . '">1</a></li>';
        if ($start > 2) $html .= '<li class="dots">…</li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? ' class="active"' : '';
        $html  .= "<li$active><a href=\"" . htmlspecialchars($baseUrl . $i) . "\">$i</a></li>";
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<li class="dots">…</li>';
        $html .= '<li><a href="' . htmlspecialchars($baseUrl . $totalPages) . '">' . $totalPages . '</a></li>';
    }

    if ($currentPage < $totalPages) {
        $html .= '<li><a href="' . htmlspecialchars($baseUrl . ($currentPage + 1)) . '" aria-label="Next">Next &raquo;</a></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

// ── Sanitization ──────────────────────────────────────────────────────────────

function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function truncate(string $text, int $length = 160): string {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '…';
}

// ── Date formatting ───────────────────────────────────────────────────────────

function formatDate(string $date, string $format = 'F j, Y'): string {
    return date($format, strtotime($date));
}

function timeAgo(string $date): string {
    $diff = time() - strtotime($date);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return formatDate($date);
}

// ── Flash messages ────────────────────────────────────────────────────────────

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    $type = e($flash['type']);
    $msg  = e($flash['message']);
    return "<div class=\"alert alert-$type\">$msg</div>";
}

// ── Popular Posts ─────────────────────────────────────────────

function getPopularPosts(int $limit = 5): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM   posts p
        LEFT   JOIN categories c ON c.id = p.category_id
        WHERE  p.status = 'published'
        ORDER  BY p.views DESC
        LIMIT  ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// ── Tags (all) ────────────────────────────────────────────────

function getAllTagsWithCount(int $limit = 20): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT t.*, COUNT(pt.post_id) AS post_count
        FROM   tags t
        INNER  JOIN post_tags pt ON pt.tag_id = t.id
        GROUP  BY t.id
        ORDER  BY post_count DESC
        LIMIT  ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}
