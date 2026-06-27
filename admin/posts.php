<?php
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\') . '/');

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
$admin = getCurrentAdmin();
$pdo   = getDB();

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action   = $_POST['bulk_action'] ?? '';
    $selected = array_map('intval', $_POST['selected'] ?? []);

    if (!empty($selected)) {
        if ($action === 'delete') {
            $in = implode(',', $selected);
            $pdo->exec("DELETE FROM posts WHERE id IN ($in)");
            setFlash('success', 'Selected posts deleted.');
        } elseif ($action === 'publish') {
            $in = implode(',', $selected);
            $pdo->exec("UPDATE posts SET status='published' WHERE id IN ($in)");
            setFlash('success', 'Selected posts published.');
        } elseif ($action === 'draft') {
            $in = implode(',', $selected);
            $pdo->exec("UPDATE posts SET status='draft' WHERE id IN ($in)");
            setFlash('success', 'Selected posts moved to draft.');
        }
    }
    header('Location: ' . BASE_URL . 'admin/posts.php');
    exit;
}

// Single delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (verifyCsrfToken($_GET['token'] ?? '')) {
        $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([(int)$_GET['delete']]);
        setFlash('success', 'Post deleted successfully.');
    }
    header('Location: ' . BASE_URL . 'admin/posts.php');
    exit;
}

// Filters
$statusFilter = $_GET['status'] ?? 'all';
$catFilter    = (int)($_GET['category'] ?? 0);
$search       = trim($_GET['search'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 15;

$where  = ['1=1'];
$params = [];

if ($statusFilter === 'published') { $where[] = "p.status = 'published'"; }
elseif ($statusFilter === 'draft') { $where[] = "p.status = 'draft'"; }

if ($catFilter) { $where[] = 'p.category_id = ?'; $params[] = $catFilter; }
if ($search)    { $where[] = '(p.title LIKE ? OR p.slug LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$whereStr = implode(' AND ', $where);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM posts p WHERE $whereStr");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$listParams = array_merge($params, [$perPage, $offset]);
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, u.display_name AS author_name,
           (SELECT COUNT(*) FROM comments WHERE post_id=p.id) AS comment_count,
           (SELECT COUNT(*) FROM likes     WHERE post_id=p.id) AS like_count
    FROM posts p
    LEFT JOIN categories c ON c.id=p.category_id
    LEFT JOIN users u ON u.id=p.author_id
    WHERE $whereStr
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($listParams);
$posts = $stmt->fetchAll();

$categories    = getCategories();
$pendingComments = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE approved=0")->fetchColumn();
$pageTitle = 'Manage Posts';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
</head>
<body>
<div class="admin-wrapper">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div style="flex:1;display:flex;flex-direction:column;min-width:0;">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <main class="admin-main">

      <?= renderFlash() ?>

      <!-- Filters -->
      <div class="admin-card" style="margin-bottom:1.25rem;">
        <div class="admin-card-body" style="padding:1rem 1.5rem;">
          <form method="get" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
            <div>
              <label class="form-label" style="font-size:.75rem;">Status</label>
              <select name="status" class="form-control" style="width:140px;" onchange="this.form.submit()">
                <option value="all"       <?= $statusFilter==='all'       ?'selected':''?>>All</option>
                <option value="published" <?= $statusFilter==='published' ?'selected':''?>>Published</option>
                <option value="draft"     <?= $statusFilter==='draft'     ?'selected':''?>>Draft</option>
              </select>
            </div>
            <div>
              <label class="form-label" style="font-size:.75rem;">Category</label>
              <select name="category" class="form-control" style="width:160px;" onchange="this.form.submit()">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $catFilter==$cat['id']?'selected':''?>>
                  <?= e($cat['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div style="flex:1;min-width:200px;">
              <label class="form-label" style="font-size:.75rem;">Search</label>
              <input type="search" name="search" value="<?= e($search) ?>" placeholder="Search posts…" class="form-control">
            </div>
            <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-search"></i></button>
            <?php if ($search || $catFilter || $statusFilter!=='all'): ?>
            <a href="<?= BASE_URL ?>admin/posts.php" class="btn btn-secondary btn-sm">Clear</a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <!-- Bulk action form -->
      <form id="bulk-action-form" method="post">
        <?= csrfField() ?>
        <div class="admin-card">
          <div class="admin-card-header">
            <h3 class="admin-card-title">
              Posts
              <span style="color:var(--text-muted);font-size:.8rem;font-weight:400;margin-left:.5rem;"><?= $total ?> total</span>
            </h3>
            <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
              <select name="bulk_action" class="form-control" style="width:160px;">
                <option value="">Bulk Action…</option>
                <option value="publish">Set Published</option>
                <option value="draft">Set Draft</option>
                <option value="delete">Delete</option>
              </select>
              <button type="submit" id="bulk-apply" class="btn btn-secondary btn-sm" disabled>Apply</button>
              <a href="<?= BASE_URL ?>admin/new-post.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> New Post
              </a>
            </div>
          </div>

          <div class="table-responsive">
            <table class="admin-table">
              <thead>
                <tr>
                  <th style="width:40px;">
                    <input type="checkbox" id="select-all" aria-label="Select all">
                  </th>
                  <th></th>
                  <th>Title</th>
                  <th>Category</th>
                  <th>Status</th>
                  <th>Views</th>
                  <th>Comments</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($posts)): ?>
                <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text-muted);">No posts found.</td></tr>
                <?php else: ?>
                <?php foreach ($posts as $post): ?>
                <tr>
                  <td><input type="checkbox" name="selected[]" value="<?= $post['id'] ?>" aria-label="Select post"></td>
                  <td>
                    <?php if ($post['featured_image']): ?>
                    <img src="<?= e($post['featured_image']) ?>" alt="" class="post-thumb">
                    <?php else: ?>
                    <div style="width:48px;height:36px;background:var(--border);border-radius:4px;"></div>
                    <?php endif; ?>
                  </td>
                  <td class="post-title-cell">
                    <a href="<?= BASE_URL ?>admin/new-post.php?id=<?= $post['id'] ?>"><?= e($post['title']) ?></a>
                    <small>by <?= e($post['author_name']) ?></small>
                  </td>
                  <td><?= e($post['category_name'] ?? '—') ?></td>
                  <td><span class="status-badge status-<?= $post['status'] ?>"><?= $post['status'] ?></span></td>
                  <td><?= number_format($post['views']) ?></td>
                  <td><?= $post['comment_count'] ?></td>
                  <td style="white-space:nowrap;"><?= formatDate($post['created_at'], 'M j, Y') ?></td>
                  <td>
                    <div class="action-btns">
                      <a href="<?= BASE_URL ?>admin/new-post.php?id=<?= $post['id'] ?>" class="btn-action edit" title="Edit">
                        <i class="fas fa-edit"></i>
                      </a>
                      <a href="<?= BASE_URL ?>post.php?slug=<?= e($post['slug']) ?>" class="btn-action view" title="View" target="_blank">
                        <i class="fas fa-eye"></i>
                      </a>
                      <a href="<?= BASE_URL ?>admin/posts.php?delete=<?= $post['id'] ?>&token=<?= e(generateCsrfToken()) ?>"
                         class="btn-action delete"
                         title="Delete"
                         data-confirm="Delete &quot;<?= e(addslashes($post['title'])) ?>&quot;? This cannot be undone.">
                        <i class="fas fa-trash"></i>
                      </a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </form>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <nav class="pagination">
        <ul>
          <?php if ($page > 1): ?>
          <li><a href="?page=<?= $page-1 ?>&status=<?= e($statusFilter) ?>&search=<?= urlencode($search) ?>">&laquo;</a></li>
          <?php endif; ?>
          <?php for ($i=1;$i<=$totalPages;$i++): ?>
          <li <?= $i===$page?'class="active"':'' ?>><a href="?page=<?= $i ?>&status=<?= e($statusFilter) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a></li>
          <?php endfor; ?>
          <?php if ($page < $totalPages): ?>
          <li><a href="?page=<?= $page+1 ?>&status=<?= e($statusFilter) ?>&search=<?= urlencode($search) ?>">&raquo;</a></li>
          <?php endif; ?>
        </ul>
      </nav>
      <style>.pagination { margin-top:1.5rem; justify-content:flex-start; } .pagination a,.pagination .active a { color:var(--text-muted); }</style>
      <?php endif; ?>

    </main>
  </div>
</div>
<script src="<?= BASE_URL ?>assets/js/admin.js" defer></script>
</body>
</html>
