<?php
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\') . '/');

require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$category = getCategoryBySlug($slug);
if (!$category) {
    http_response_code(404);
    $pageTitle = 'Category Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container" style="padding:4rem 1.5rem;text-align:center;">
        <h1>Category Not Found</h1>
        <a href="' . BASE_URL . 'index.php" class="btn btn-primary" style="margin-top:1.5rem;">Go Home</a>
    </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = (int)getSetting('posts_per_page', '6');
$total      = countPosts($category['id']);
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$posts      = getPosts($page, $perPage, $category['id']);

$pageTitle       = $category['name'] . ' — ' . getSetting('site_name', 'ModernBlog');
$pageDescription = $category['description'] ?: "Browse all posts in the {$category['name']} category.";

require_once __DIR__ . '/includes/header.php';
?>

<!-- Category Hero -->
<div class="category-hero">
  <div class="container">
    <span class="badge" style="background:rgba(255,255,255,.2);color:#fff;margin-bottom:.75rem;display:inline-flex;">
      <i class="fas fa-folder-open" style="margin-right:.35rem;"></i> Category
    </span>
    <h1><?= e($category['name']) ?></h1>
    <?php if ($category['description']): ?>
    <p><?= e($category['description']) ?></p>
    <?php endif; ?>
    <p style="margin-top:.5rem;opacity:.8;font-size:.875rem;"><?= $total ?> post<?= $total !== 1 ? 's' : '' ?></p>
  </div>
</div>

<div class="container" style="padding-bottom:4rem;">

  <div style="display:grid;grid-template-columns:1fr 280px;gap:3rem;" class="cat-layout">

    <div>
      <?php if (empty($posts)): ?>
      <div style="text-align:center;padding:4rem 2rem;color:var(--text-muted);">
        <i class="fas fa-folder-open" style="font-size:3rem;display:block;margin-bottom:1rem;"></i>
        <p>No posts in this category yet.</p>
      </div>
      <?php else: ?>
      <div class="grid-auto" style="margin-bottom:2rem;">
        <?php foreach ($posts as $post): ?>
        <article class="card">
          <?php if ($post['featured_image']): ?>
          <img src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" class="card-img" loading="lazy">
          <?php else: ?>
          <div style="height:210px;background:var(--gradient);opacity:.7;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-image" style="color:#fff;font-size:2.5rem;"></i>
          </div>
          <?php endif; ?>
          <div class="card-body">
            <div class="card-meta">
              <span class="badge"><?= e($category['name']) ?></span>
              <span class="text-xs text-muted"><?= readingTime($post['content']) ?> min read</span>
            </div>
            <h3 class="card-title">
              <a href="<?= BASE_URL ?>post.php?slug=<?= e($post['slug']) ?>"><?= e($post['title']) ?></a>
            </h3>
            <p class="card-excerpt"><?= e(truncate($post['excerpt'] ?? $post['content'], 130)) ?></p>
            <a href="<?= BASE_URL ?>post.php?slug=<?= e($post['slug']) ?>" class="btn btn-secondary btn-sm" style="margin-top:.5rem;">
              Read More <i class="fas fa-arrow-right"></i>
            </a>
          </div>
          <div class="card-footer">
            <span><?= formatDate($post['created_at'], 'M j, Y') ?></span>
            <div class="card-stats">
              <span class="card-stat"><i class="fas fa-eye"></i> <?= number_format($post['views']) ?></span>
              <span class="card-stat"><i class="fas fa-heart"></i> <?= number_format($post['like_count'] ?? 0) ?></span>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <?= paginationLinks($page, $totalPages, BASE_URL . 'category.php?slug=' . e($category['slug']) . '&page=') ?>
      <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-widget">
        <h3 class="widget-title"><i class="fas fa-th-large"></i> All Categories</h3>
        <ul class="widget-category-list">
          <?php foreach ($categories as $cat): ?>
          <li>
            <a href="<?= BASE_URL ?>category.php?slug=<?= e($cat['slug']) ?>"
               style="<?= $cat['slug'] === $slug ? 'color:var(--primary);font-weight:700;' : '' ?>">
              <?= e($cat['name']) ?>
            </a>
            <span class="widget-category-count"><?= (int)$cat['post_count'] ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="sidebar-widget">
        <h3 class="widget-title"><i class="fas fa-search"></i> Search</h3>
        <form action="<?= BASE_URL ?>search.php" method="get">
          <div style="display:flex;gap:.5rem;">
            <input type="search" name="q" placeholder="Search posts…" class="form-control" required>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
          </div>
        </form>
      </div>
    </aside>

  </div>
</div>

<style>
@media (max-width:1024px) { .cat-layout { grid-template-columns: 1fr !important; } }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
