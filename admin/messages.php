<?php
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\') . '/');

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
$admin = getCurrentAdmin();
$pdo   = getDB();

// Mark as read single
if (isset($_GET['read']) && is_numeric($_GET['read']) && verifyCsrfToken($_GET['token'] ?? '')) {
    $pdo->prepare("UPDATE contact_messages SET read_status=1 WHERE id=?")->execute([(int)$_GET['read']]);
    setFlash('success', 'Message marked as read.');
    header('Location: ' . BASE_URL . 'admin/messages.php'); exit;
}

// Delete single
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && verifyCsrfToken($_GET['token'] ?? '')) {
    $pdo->prepare("DELETE FROM contact_messages WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlash('success', 'Message deleted.');
    header('Location: ' . BASE_URL . 'admin/messages.php'); exit;
}

// Bulk action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action   = $_POST['bulk_action'] ?? '';
    $selected = array_map('intval', $_POST['selected'] ?? []);
    if (!empty($selected)) {
        $in = implode(',', $selected);
        if ($action === 'read')    { $pdo->exec("UPDATE contact_messages SET read_status=1 WHERE id IN ($in)"); setFlash('success', 'Messages marked as read.'); }
        elseif ($action === 'unread') { $pdo->exec("UPDATE contact_messages SET read_status=0 WHERE id IN ($in)"); setFlash('success', 'Messages marked as unread.'); }
        elseif ($action === 'delete') { $pdo->exec("DELETE FROM contact_messages WHERE id IN ($in)"); setFlash('success', 'Messages deleted.'); }
    }
    header('Location: ' . BASE_URL . 'admin/messages.php'); exit;
}

// View single message
$viewMsg = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id=?");
    $stmt->execute([(int)$_GET['view']]);
    $viewMsg = $stmt->fetch();
    if ($viewMsg && !$viewMsg['read_status']) {
        $pdo->prepare("UPDATE contact_messages SET read_status=1 WHERE id=?")->execute([$viewMsg['id']]);
        $viewMsg['read_status'] = 1;
    }
}

$statusFilter = $_GET['status'] ?? 'all';
$search       = trim($_GET['search'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;

$where  = ['1=1'];
$params = [];
if ($statusFilter === 'unread') { $where[] = 'read_status=0'; }
elseif ($statusFilter === 'read') { $where[] = 'read_status=1'; }
if ($search) { $where[] = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)'; $params = array_merge($params, ["%$search%","%$search%","%$search%","%$search%"]); }
$whereStr = implode(' AND ', $where);

$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE $whereStr");
$cntStmt->execute($params);
$total      = (int)$cntStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE $whereStr ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$perPage, $offset]));
$messages = $stmt->fetchAll();

$pendingComments = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE approved=0")->fetchColumn();
$unreadMessages  = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE read_status=0")->fetchColumn();
$pageTitle = 'Contact Messages';
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

      <?php if ($viewMsg): ?>
      <!-- Single message view -->
      <div class="admin-card" style="max-width:700px;">
        <div class="admin-card-header">
          <div>
            <h3 class="admin-card-title"><?= e($viewMsg['subject']) ?></h3>
            <div style="font-size:.8rem;color:var(--text-muted);margin-top:.25rem;">
              From <strong><?= e($viewMsg['name']) ?></strong>
              &lt;<a href="mailto:<?= e($viewMsg['email']) ?>"><?= e($viewMsg['email']) ?></a>&gt;
              · <?= formatDate($viewMsg['created_at'], 'F j, Y g:i A') ?>
            </div>
          </div>
          <a href="<?= BASE_URL ?>admin/messages.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back
          </a>
        </div>
        <div class="admin-card-body">
          <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.5rem;white-space:pre-wrap;font-size:.95rem;line-height:1.7;color:var(--text-muted);">
            <?= e($viewMsg['message']) ?>
          </div>
          <div style="margin-top:1.5rem;display:flex;gap:.75rem;flex-wrap:wrap;">
            <a href="mailto:<?= e($viewMsg['email']) ?>?subject=Re: <?= rawurlencode($viewMsg['subject']) ?>"
               class="btn btn-primary">
              <i class="fas fa-reply"></i> Reply via Email
            </a>
            <a href="?delete=<?= $viewMsg['id'] ?>&token=<?= e(generateCsrfToken()) ?>"
               class="btn btn-secondary"
               data-confirm="Delete this message permanently?"
               style="color:var(--danger);border-color:var(--danger);">
              <i class="fas fa-trash"></i> Delete
            </a>
          </div>
        </div>
      </div>

      <?php else: ?>

      <!-- Filters -->
      <div class="admin-card" style="margin-bottom:1.25rem;">
        <div class="admin-card-body" style="padding:1rem 1.5rem;">
          <form method="get" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">
            <div>
              <label class="form-label" style="font-size:.75rem;">Status</label>
              <select name="status" class="form-control" style="width:160px;" onchange="this.form.submit()">
                <option value="all"    <?= $statusFilter==='all'    ?'selected':''?>>All</option>
                <option value="unread" <?= $statusFilter==='unread' ?'selected':''?>>Unread <?= $unreadMessages ? "($unreadMessages)" : '' ?></option>
                <option value="read"   <?= $statusFilter==='read'   ?'selected':''?>>Read</option>
              </select>
            </div>
            <div style="flex:1;min-width:200px;">
              <label class="form-label" style="font-size:.75rem;">Search</label>
              <input type="search" name="search" value="<?= e($search) ?>" placeholder="Search by name, email, subject…" class="form-control">
            </div>
            <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-search"></i></button>
            <?php if ($search || $statusFilter !== 'all'): ?>
            <a href="<?= BASE_URL ?>admin/messages.php" class="btn btn-secondary btn-sm">Clear</a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <form id="bulk-action-form" method="post">
        <?= csrfField() ?>
        <div class="admin-card">
          <div class="admin-card-header">
            <h3 class="admin-card-title">
              Messages
              <?php if ($unreadMessages): ?>
              <span class="status-badge status-pending" style="margin-left:.5rem;"><?= $unreadMessages ?> unread</span>
              <?php endif; ?>
              <span style="color:var(--text-muted);font-size:.8rem;font-weight:400;margin-left:.5rem;"><?= $total ?> total</span>
            </h3>
            <div style="display:flex;gap:.75rem;align-items:center;">
              <select name="bulk_action" class="form-control" style="width:180px;">
                <option value="">Bulk Action…</option>
                <option value="read">Mark as Read</option>
                <option value="unread">Mark as Unread</option>
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
                  <th>From</th>
                  <th>Subject</th>
                  <th>Preview</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($messages)): ?>
                <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">No messages found.</td></tr>
                <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                <tr style="<?= !$msg['read_status'] ? 'font-weight:600;' : 'opacity:.75;' ?>">
                  <td><input type="checkbox" name="selected[]" value="<?= $msg['id'] ?>"></td>
                  <td>
                    <div style="font-size:.875rem;"><?= e($msg['name']) ?></div>
                    <div style="font-size:.75rem;color:var(--text-muted);"><?= e($msg['email']) ?></div>
                  </td>
                  <td style="max-width:200px;font-size:.875rem;">
                    <a href="?view=<?= $msg['id'] ?>" style="color:var(--text);">
                      <?= e(truncate($msg['subject'], 50)) ?>
                    </a>
                  </td>
                  <td style="max-width:250px;font-size:.8rem;color:var(--text-muted);">
                    <?= e(truncate($msg['message'], 80)) ?>
                  </td>
                  <td>
                    <span class="status-badge <?= $msg['read_status'] ? 'status-approved' : 'status-pending' ?>">
                      <?= $msg['read_status'] ? 'Read' : 'Unread' ?>
                    </span>
                  </td>
                  <td style="white-space:nowrap;font-size:.8rem;"><?= formatDate($msg['created_at'], 'M j, Y') ?></td>
                  <td>
                    <div class="action-btns">
                      <a href="?view=<?= $msg['id'] ?>" class="btn-action view" title="View"><i class="fas fa-eye"></i></a>
                      <?php if (!$msg['read_status']): ?>
                      <a href="?read=<?= $msg['id'] ?>&token=<?= e(generateCsrfToken()) ?>" class="btn-action approve" title="Mark Read"><i class="fas fa-check"></i></a>
                      <?php endif; ?>
                      <a href="mailto:<?= e($msg['email']) ?>?subject=Re: <?= rawurlencode($msg['subject']) ?>" class="btn-action edit" title="Reply"><i class="fas fa-reply"></i></a>
                      <a href="?delete=<?= $msg['id'] ?>&token=<?= e(generateCsrfToken()) ?>"
                         class="btn-action delete" title="Delete"
                         data-confirm="Delete this message permanently?">
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

      <?php endif; ?>

    </main>
  </div>
</div>
<script src="<?= BASE_URL ?>assets/js/admin.js" defer></script>
</body>
</html>
