<?php
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\') . '/');

require_once __DIR__ . '/includes/functions.php';

$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = (int)getSetting('posts_per_page', '6');
$totalPosts  = countPosts();
$totalPages  = max(1, (int)ceil($totalPosts / $perPage));
$page        = min($page, $totalPages);

$posts         = getPosts($page, $perPage);
$featuredPosts = getFeaturedPosts(3);
$sidebarPosts  = getPosts(1, 5);

$pageTitle       = getSetting('site_name', 'ModernBlog') . ' — Modern Tech Blog';
$pageDescription = getSetting('site_description', 'Discover articles on technology, design, and tutorials.');

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="hero">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:center;" class="hero-grid-resp">
      <div class="hero-inner">
        <span class="badge badge-secondary" style="background:rgba(255,255,255,.2);color:#fff;margin-bottom:1rem;display:inline-flex;">
          <i class="fas fa-fire" style="margin-right:.35rem;"></i> Latest from the Blog
        </span>
        <h1>Ideas Worth<br>Reading About</h1>
        <p>Explore in-depth articles on web development, design, and the technology that shapes our world.</p>
        <div class="hero-actions">
          <a href="<?= BASE_URL ?>search.php" class="btn btn-white">
            <i class="fas fa-search"></i> Explore Posts
          </a>
          <a href="<?= BASE_URL ?>about.php" class="btn btn-outline-white">
            <i class="fas fa-info-circle"></i> About Us
          </a>
        </div>
      </div>

      <?php if (!empty($featuredPosts[0])): $fp = $featuredPosts[0]; ?>
      <div>
        <div class="hero-featured">
          <span class="badge" style="background:rgba(255,255,255,.2);color:#fff;">
            <i class="fas fa-star" style="margin-right:.3rem;"></i> Featured
          </span>
          <?php if ($fp['featured_image']): ?>
          <img src="<?= e($fp['featured_image']) ?>" alt="<?= e($fp['title']) ?>"
               loading="lazy"
               style="width:100%;height:180px;object-fit:cover;border-radius:10px;margin:.75rem 0;">
          <?php endif; ?>
          <h2><a href="<?= BASE_URL ?>post.php?slug=<?= e($fp['slug']) ?>"><?= e($fp['title']) ?></a></h2>
          <p><?= e(truncate($fp['excerpt'] ?? $fp['content'], 100)) ?></p>
          <div style="margin-top:.75rem;display:flex;align-items:center;gap:1rem;font-size:.8rem;color:rgba(255,255,255,.7);">
            <span><i class="fas fa-eye"></i> <?= number_format($fp['views']) ?></span>
            <span><i class="fas fa-clock"></i> <?= readingTime($fp['content']) ?> min</span>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="container">

  <!-- Featured Posts Row -->
  <?php if (!empty($featuredPosts)): ?>
  <section class="section-sm" style="padding-top:3rem;">
    <div class="section-header">
      <div class="section-header-text">
        <h2>Featured Posts</h2>
        <p>Handpicked articles you shouldn't miss</p>
      </div>
    </div>
    <div class="grid-3 grid-resp">
      <?php foreach ($featuredPosts as $fp): ?>
      <article class="card">
        <?php if ($fp['featured_image']): ?>
        <img src="<?= e($fp['featured_image']) ?>" alt="<?= e($fp['title']) ?>" class="card-img" loading="lazy">
        <?php else: ?>
        <div style="height:210px;background:var(--gradient);display:flex;align-items:center;justify-content:center;">
          <i class="fas fa-image" style="color:rgba(255,255,255,.3);font-size:2.5rem;"></i>
        </div>
        <?php endif; ?>
        <div class="card-body">
          <div class="card-meta">
            <?php if ($fp['category_name']): ?>
            <a href="<?= BASE_URL ?>category.php?slug=<?= e($fp['category_slug']) ?>" class="badge">
              <?= e($fp['category_name']) ?>
            </a>
            <?php endif; ?>
            <span class="text-xs text-muted">
              <i class="fas fa-clock"></i> <?= readingTime($fp['content']) ?> min read
            </span>
          </div>
          <h3 class="card-title">
            <a href="<?= BASE_URL ?>post.php?slug=<?= e($fp['slug']) ?>"><?= e($fp['title']) ?></a>
          </h3>
          <p class="card-excerpt"><?= e(truncate($fp['excerpt'] ?? $fp['content'], 130)) ?></p>
        </div>
        <div class="card-footer">
          <span><?= formatDate($fp['created_at']) ?></span>
          <div class="card-stats">
            <span class="card-stat"><i class="fas fa-eye"></i> <?= number_format($fp['views']) ?></span>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Main + Sidebar Layout -->
  <div style="display:grid;grid-template-columns:1fr 300px;gap:3rem;padding:2rem 0 4rem;" class="main-layout-resp">

    <!-- Posts Grid -->
    <section>
      <div class="section-header">
        <div class="section-header-text">
          <h2>Latest Articles</h2>
          <p><?= $totalPosts ?> posts published</p>
        </div>
        <?php if ($page > 1): ?>
        <span class="badge badge-secondary">Page <?= $page ?></span>
        <?php endif; ?>
      </div>

      <?php if (empty($posts)): ?>
      <div style="text-align:center;padding:4rem 2rem;color:var(--text-muted);">
        <i class="fas fa-inbox" style="font-size:3rem;margin-bottom:1rem;display:block;"></i>
        <p>No posts yet. Check back soon!</p>
      </div>
      <?php else: ?>
      <div class="grid-auto">
        <?php foreach ($posts as $post): ?>
        <article class="card">
          <?php if ($post['featured_image']): ?>
          <img src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" class="card-img" loading="lazy">
          <?php else: ?>
          <div style="height:210px;background:var(--gradient);display:flex;align-items:center;justify-content:center;opacity:.6;">
            <i class="fas fa-image" style="color:#fff;font-size:2.5rem;"></i>
          </div>
          <?php endif; ?>
          <div class="card-body">
            <div class="card-meta">
              <?php if ($post['category_name']): ?>
              <a href="<?= BASE_URL ?>category.php?slug=<?= e($post['category_slug']) ?>" class="badge">
                <?= e($post['category_name']) ?>
              </a>
              <?php endif; ?>
              <?php if ($post['featured']): ?>
              <span class="badge badge-warning"><i class="fas fa-star"></i> Featured</span>
              <?php endif; ?>
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
            <span style="display:flex;align-items:center;gap:.4rem;">
              <i class="fas fa-user-circle" style="color:var(--primary);"></i>
              <?= e($post['author_name']) ?>
            </span>
            <div class="card-stats">
              <span class="card-stat"><i class="fas fa-eye"></i> <?= number_format($post['views']) ?></span>
              <span class="card-stat"><i class="fas fa-heart"></i> <?= number_format($post['like_count'] ?? 0) ?></span>
              <span class="card-stat"><i class="fas fa-comment"></i> <?= number_format($post['comment_count'] ?? 0) ?></span>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?= paginationLinks($page, $totalPages, BASE_URL . 'index.php?page=') ?>
      <?php endif; ?>
    </section>

    <!-- Sidebar -->
    <aside class="sidebar">

      <!-- Search Widget -->
      <div class="sidebar-widget">
        <h3 class="widget-title"><i class="fas fa-search"></i> Search</h3>
        <form action="<?= BASE_URL ?>search.php" method="get">
          <div style="display:flex;gap:.5rem;">
            <input type="search" name="q" placeholder="Search posts…" class="form-control" required aria-label="Search">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
          </div>
        </form>
      </div>

      <!-- Categories Widget -->
      <div class="sidebar-widget">
        <h3 class="widget-title"><i class="fas fa-folder"></i> Categories</h3>
        <ul class="widget-category-list">
          <?php foreach ($categories as $cat): ?>
          <li>
            <a href="<?= BASE_URL ?>category.php?slug=<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></a>
            <span class="widget-category-count"><?= (int)$cat['post_count'] ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Recent Posts Widget -->
      <div class="sidebar-widget">
        <h3 class="widget-title"><i class="fas fa-clock"></i> Recent Posts</h3>
        <?php foreach ($sidebarPosts as $sp): ?>
        <div class="widget-post">
          <?php if ($sp['featured_image']): ?>
          <img src="<?= e($sp['featured_image']) ?>" alt="<?= e($sp['title']) ?>" loading="lazy">
          <?php endif; ?>
          <div>
            <a href="<?= BASE_URL ?>post.php?slug=<?= e($sp['slug']) ?>" class="widget-post-title">
              <?= e($sp['title']) ?>
            </a>
            <div class="widget-post-date"><i class="fas fa-calendar-alt"></i> <?= formatDate($sp['created_at'], 'M j, Y') ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Popular Posts Widget -->
      <?php $popularPosts = getPopularPosts(4); if (!empty($popularPosts)): ?>
      <div class="sidebar-widget">
        <h3 class="widget-title"><i class="fas fa-fire"></i> Popular Posts</h3>
        <?php foreach ($popularPosts as $i => $pp): ?>
        <div class="widget-post" style="align-items:center;">
          <div style="width:28px;height:28px;background:var(--gradient);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.8rem;flex-shrink:0;">
            <?= $i + 1 ?>
          </div>
          <div>
            <a href="<?= BASE_URL ?>post.php?slug=<?= e($pp['slug']) ?>" class="widget-post-title">
              <?= e($pp['title']) ?>
            </a>
            <div class="widget-post-date"><i class="fas fa-eye"></i> <?= number_format($pp['views']) ?> views</div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- RSS Widget -->
      <div class="sidebar-widget" style="text-align:center;">
        <h3 class="widget-title"><i class="fas fa-rss"></i> Subscribe</h3>
        <p style="font-size:.875rem;color:var(--text-muted);margin-bottom:1rem;">Never miss a new post.</p>
        <a href="<?= BASE_URL ?>feed.php" class="btn btn-primary" style="width:100%;justify-content:center;">
          <i class="fas fa-rss"></i> RSS Feed
        </a>
      </div>

    </aside>
  </div><!-- /main layout -->

</div><!-- /container -->

<style>
@media (max-width:1024px) {
  .main-layout-resp { grid-template-columns: 1fr !important; }
  .sidebar { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px,1fr)); }
}
@media (max-width:768px) {
  .hero-grid-resp { grid-template-columns: 1fr !important; }
  .grid-3.grid-resp { grid-template-columns: 1fr !important; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
