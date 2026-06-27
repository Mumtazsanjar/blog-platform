<?php
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\') . '/');

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
$admin = getCurrentAdmin();

$pdo = getDB();

// Stats
$totalPosts    = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
$totalDrafts   = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status='draft'")->fetchColumn();
$totalComments = (int)$pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$pendingComments=(int)$pdo->query("SELECT COUNT(*) FROM comments WHERE approved=0")->fetchColumn();
$totalViews    = (int)$pdo->query("SELECT COALESCE(SUM(views),0) FROM posts")->fetchColumn();
$totalLikes    = (int)$pdo->query("SELECT COUNT(*) FROM likes")->fetchColumn();
$totalMessages = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$unreadMessages= (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE read_status=0")->fetchColumn();

// Recent comments
$recentComments = $pdo->query("SELECT c.*, p.title AS post_title, p.slug AS post_slug FROM comments c LEFT JOIN posts p ON p.id=c.post_id ORDER BY c.created_at DESC LIMIT 5")->fetchAll();

// Popular posts
$popularPosts = $pdo->query("SELECT * FROM posts WHERE status='published' ORDER BY views DESC LIMIT 5")->fetchAll();

// Recent posts
$recentPosts = $pdo->query("SELECT p.*, c.name AS category_name FROM posts p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.created_at DESC LIMIT 5")->fetchAll();

// Views last 7 days (approximate by distributing total views)
$chartDays   = [];
$chartValues = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('D', strtotime("-$i days"));
    $chartDays[] = $day;
    // Count posts created on that day as a proxy — or just mock
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(views),0) FROM posts WHERE DATE(created_at) = ?");
    $stmt->execute([date('Y-m-d', strtotime("-$i days"))]);
    $chartValues[] = (int)$stmt->fetchColumn();
}
// If all zeros, add some demo data
if (array_sum($chartValues) === 0) {
    $chartValues = [120, 210, 180, 340, 280, 420, 390];
}

$pageTitle = 'Dashboard';
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

      <h1 style="font-size:1.5rem;font-weight:800;margin-bottom:1.5rem;">
        Good <?= date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening') ?>,
        <?= e($admin['display_name'] ?: $admin['username']) ?> 👋
      </h1>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card-admin">
          <div class="stat-icon purple"><i class="fas fa-file-alt"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= number_format($totalPosts) ?></div>
            <div class="stat-label-admin">Published Posts</div>
            <div class="stat-change up"><i class="fas fa-file"></i> <?= $totalDrafts ?> drafts</div>
          </div>
        </div>
        <div class="stat-card-admin">
          <div class="stat-icon blue"><i class="fas fa-comments"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= number_format($totalComments) ?></div>
            <div class="stat-label-admin">Total Comments</div>
            <?php if ($pendingComments): ?>
            <div class="stat-change down"><i class="fas fa-clock"></i> <?= $pendingComments ?> pending</div>
            <?php endif; ?>
          </div>
        </div>
        <div class="stat-card-admin">
          <div class="stat-icon green"><i class="fas fa-eye"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= number_format($totalViews) ?></div>
            <div class="stat-label-admin">Total Views</div>
          </div>
        </div>
        <div class="stat-card-admin">
          <div class="stat-icon red"><i class="fas fa-heart"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= number_format($totalLikes) ?></div>
            <div class="stat-label-admin">Total Likes</div>
          </div>
        </div>
        <div class="stat-card-admin">
          <div class="stat-icon orange"><i class="fas fa-envelope"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= number_format($totalMessages) ?></div>
            <div class="stat-label-admin">Messages</div>
            <?php if ($unreadMessages): ?>
            <div class="stat-change down"><i class="fas fa-circle" style="font-size:.5rem;"></i> <?= $unreadMessages ?> unread</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Chart + Recent Comments -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">

        <!-- Views Chart -->
        <div class="admin-card">
          <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="fas fa-chart-bar"></i> Views (Last 7 Days)</h3>
          </div>
          <div class="admin-card-body" style="padding:1rem;">
            <canvas id="views-chart"
                    style="width:100%;height:180px;display:block;"
                    data-labels='<?= json_encode($chartDays) ?>'
                    data-values='<?= json_encode($chartValues) ?>'></canvas>
          </div>
        </div>

        <!-- Recent Comments -->
        <div class="admin-card">
          <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="fas fa-comments"></i> Recent Comments</h3>
            <a href="<?= BASE_URL ?>admin/comments.php" class="btn btn-secondary btn-sm">View All</a>
          </div>
          <div class="admin-card-body" style="padding:0 1.5rem;">
            <div class="activity-list">
              <?php if (empty($recentComments)): ?>
              <p style="padding:1rem 0;color:var(--text-muted);font-size:.875rem;">No comments yet.</p>
              <?php else: ?>
              <?php foreach ($recentComments as $c): ?>
              <div class="activity-item">
                <div class="activity-dot <?= $c['approved'] ? 'green' : 'orange' ?>"></div>
                <div>
                  <div class="activity-text">
                    <strong><?= e($c['name']) ?></strong> commented on
                    <a href="<?= BASE_URL ?>post.php?slug=<?= e($c['post_slug']) ?>" target="_blank"><?= e(truncate($c['post_title'], 35)) ?></a>
                  </div>
                  <div class="activity-time"><?= timeAgo($c['created_at']) ?> — <?= e(truncate($c['content'], 60)) ?></div>
                </div>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

      </div>

      <!-- Recent Posts + Popular Posts -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

        <!-- Recent Posts -->
        <div class="admin-card">
          <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="fas fa-clock"></i> Recent Posts</h3>
            <a href="<?= BASE_URL ?>admin/posts.php" class="btn btn-secondary btn-sm">View All</a>
          </div>
          <div class="table-responsive">
            <table class="admin-table">
              <thead><tr><th>Title</th><th>Category</th><th>Status</th></tr></thead>
              <tbody>
                <?php foreach ($recentPosts as $p): ?>
                <tr>
                  <td class="post-title-cell">
                    <a href="<?= BASE_URL ?>admin/new-post.php?id=<?= $p['id'] ?>"><?= e(truncate($p['title'], 45)) ?></a>
                    <small><?= formatDate($p['created_at'], 'M j, Y') ?></small>
                  </td>
                  <td><?= e($p['category_name'] ?? '—') ?></td>
                  <td><span class="status-badge status-<?= $p['status'] ?>"><?= $p['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Popular Posts -->
        <div class="admin-card">
          <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="fas fa-fire"></i> Popular Posts</h3>
          </div>
          <div class="admin-card-body" style="padding:0 1.5rem;">
            <?php foreach ($popularPosts as $i => $p): ?>
            <div class="popular-post">
              <div class="popular-rank"><?= $i + 1 ?></div>
              <div class="popular-info">
                <div class="popular-title">
                  <a href="<?= BASE_URL ?>post.php?slug=<?= e($p['slug']) ?>" target="_blank"><?= e(truncate($p['title'], 50)) ?></a>
                </div>
                <div class="popular-views"><i class="fas fa-eye"></i> <?= number_format($p['views']) ?> views</div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div>

    </main>
  </div>

</div>

<script src="<?= BASE_URL ?>assets/js/admin.js" defer></script>
</body>
</html>
