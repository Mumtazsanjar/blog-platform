<?php
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\') . '/');

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
$admin = getCurrentAdmin();
$pdo   = getDB();

// Single actions
if (isset($_GET['approve']) && is_numeric($_GET['approve']) && verifyCsrfToken($_GET['token'] ?? '')) {
    $pdo->prepare("UPDATE comments SET approved=1 WHERE id=?")->execute([(int)$_GET['approve']]);
    setFlash('success', 'Comment approved.');
    header('Location: ' . BASE_URL . 'admin/comments.php'); exit;
}
if (isset($_GET['unapprove']) && is_numeric($_GET['unapprove']) && verifyCsrfToken($_GET['token'] ?? '')) {
    $pdo->prepare("UPDATE comments SET approved=0 WHERE id=?")->execute([(int)$_GET['unapprove']]);
    setFlash('success', 'Comment unapproved.');
    header('Location: ' . BASE_URL . 'admin/comments.php'); exit;
}
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && verifyCsrfToken($_GET['token'] ?? '')) {
    $pdo->prepare("DELETE FROM comments WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlash('success', 'Comment deleted.');
    header('Location: ' . BASE_URL . 'admin/comments.php'); exit;
}

// Bulk action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action   = $_POST['bulk_action'] ?? '';
    $selected = array_map('intval', $_POST['selected'] ?? []);
    if (!empty($selected)) {
        $in = implode(',', $selected);
        if ($action === 'approve')   { $pdo->exec("UPDATE comments SET approved=1 WHERE id IN ($in)"); setFlash('success', 'Comments approved.'); }
        elseif ($action === 'unapprove') { $pdo->exec("UPDATE comments SET approved=0 WHERE id IN ($in)"); setFlash('success', 'Comments unapproved.'); }
        elseif ($action === 'delete')    { $pdo->exec("DELETE FROM comments WHERE id IN ($in)"); setFlash('success', 'Comments deleted.'); }
    }
    header('Location: ' . BASE_URL . 'admin/comments.php'); exit;
}

$statusFilter = $_GET['status'] ?? 'all';
$search       = trim($_GET['search'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;

$where  = ['1=1'];
$params = [];
if ($statusFilter === 'approved')   { $where[] = 'c.approved=1'; }
elseif ($statusFilter === 'pending'){ $where[] = 'c.approved=0'; }
if ($search) { $where[] = '(c.name LIKE ? OR c.content LIKE ? OR c.email LIKE ?)'; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
$whereStr = implode(' AND ', $where);

$total      = (int)$pdo->prepare("SELECT COUNT(*) FROM comments c WHERE $whereStr")->execute($params) ? (int)$pdo->prepare("SELECT COUNT(*) FROM comments c WHERE $whereStr")->execute($params) : 0;
// Re-execute properly
$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM comments c WHERE $whereStr");
$cntStmt->execute($params);
$total      = (int)$cntStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT c.*, p.title AS post_title, p.slug AS post_slug
    FROM comments c
    LEFT JOIN posts p ON p.id=c.post_id
    WHERE $whereStr
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$comments = $stmt->fetchAll();

$pendingComments = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE approved=0")->fetchColumn();
$pageTitle = 'Manage Comments';
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
              <select name="status" class="form-control" style="width:160px;" onchange="this.form.submit()">
                <option value="all"      <?= $statusFilter==='all'      ?'selected':''?>>All</option>
                <option value="approved" <?= $statusFilter==='approved' ?'selected':''?>>Approved</option>
                <option value="pending"  <?= $statusFilter==='pending'  ?'selected':''?>>Pending <?= $pendingComments ? "($pendingComments)" : '' ?></option>
              </select>
            </div>
            <div style="flex:1;min-width:200px;">
              <label class="form-label" style="font-size:.75rem;">Search</label>
              <input type="search" name="search" value="<?= e($search) ?>" placeholder="Search by name, email, content…" class="form-control">
            </div>
            <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-search"></i></button>
            <?php if ($search || $statusFilter!=='all'): ?>
            <a href="<?= BASE_URL ?>admin/comments.php" class="btn btn-secondary btn-sm">Clear</a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <form id="bulk-action-form" method="post">
        <?= csrfField() ?>
        <div class="admin-card">
          <div class="admin-card-header">
            <h3 class="admin-card-title">
              Comments
              <?php if ($pendingComments): ?>
              <span class="status-badge status-pending" style="margin-left:.5rem;"><?= $pendingComments ?> pending</span>
              <?php endif; ?>
            </h3>
            <div style="display:flex;gap:.75rem;align-items:center;">
              <select name="bulk_action" class="form-control" style="width:160px;">
                <option value="">Bulk Action…</option>
                <option value="approve">Approve</option>
                <option value="unapprove">Unapprove</option>
                <option value="delete">Delete</option>
              </select>
              <button type="submit" id="bulk-apply" class="btn btn-secondary btn-sm" disabled>Apply</button>
            </div>
          </div>
          <div class="table-responsive">
            <table class="admin-table">
              <thead>
                <tr>
                  <th style="width:40px;"><input type="checkbox" id="select-all" aria-label="Select all"></th>
                  <th>Author</th>
                  <th>Comment</th>
                  <th>Post</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($comments)): ?>
                <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">No comments found.</td></tr>
                <?php else: ?>
                <?php foreach ($comments as $c): ?>
                <tr style="<?= !$c['approved'] ? 'opacity:.7;' : '' ?>">
                  <td><input type="checkbox" name="selected[]" value="<?= $c['id'] ?>"></td>
                  <td>
                    <div style="font-weight:600;font-size:.875rem;"><?= e($c['name']) ?></div>
                    <div style="font-size:.75rem;color:var(--text-muted);"><?= e($c['email']) ?></div>
                  </td>
                  <td style="max-width:280px;font-size:.875rem;color:var(--text-muted);">
                    <?= e(truncate($c['content'], 100)) ?>
                  </td>
                  <td style="font-size:.8rem;">
                    <a href="<?= BASE_URL ?>post.php?slug=<?= e($c['post_slug']) ?>" target="_blank" style="color:var(--text-muted);">
                      <?= e(truncate($c['post_title'] ?? '—', 40)) ?>
                    </a>
                  </td>
                  <td>
                    <span class="status-badge <?= $c['approved'] ? 'status-approved' : 'status-pending' ?>">
                      <?= $c['approved'] ? 'Approved' : 'Pending' ?>
                    </span>
                  </td>
                  <td style="white-space:nowrap;font-size:.8rem;"><?= formatDate($c['created_at'], 'M j, Y') ?></td>
                  <td>
                    <div class="action-btns">
                      <?php if (!$c['approved']): ?>
                      <a href="?approve=<?= $c['id'] ?>&token=<?= e(generateCsrfToken()) ?>" class="btn-action approve" title="Approve">
                        <i class="fas fa-check"></i>
                      </a>
                      <?php else: ?>
                      <a href="?unapprove=<?= $c['id'] ?>&token=<?= e(generateCsrfToken()) ?>" class="btn-action edit" title="Unapprove">
                        <i class="fas fa-ban"></i>
                      </a>
                      <?php endif; ?>
                      <a href="?delete=<?= $c['id'] ?>&token=<?= e(generateCsrfToken()) ?>"
                         class="btn-action delete" title="Delete"
                         data-confirm="Delete this comment permanently?">
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

      <?php if ($totalPages > 1): ?>
      <nav class="pagination" style="justify-content:flex-start;margin-top:1rem;">
        <ul>
          <?php for ($i=1;$i<=$totalPages;$i++): ?>
          <li <?= $i===$page?'class="active"':''?>><a href="?page=<?= $i ?>&status=<?= e($statusFilter) ?>"><?= $i ?></a></li>
          <?php endfor; ?>
        </ul>
      </nav>
      <?php endif; ?>

    </main>
  </div>
</div>
<script src="<?= BASE_URL ?>assets/js/admin.js" defer></script>
</body>
</html>
