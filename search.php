<?php
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\') . '/');

require_once __DIR__ . '/includes/functions.php';

$query = trim($_GET['q'] ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));

// AJAX request from live search
if (!empty($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    if (strlen($query) < 2) { echo json_encode([]); exit; }
    $results = searchPosts($query, 1, 5);
    $out = array_map(fn($p) => [
        'slug'           => $p['slug'],
        'title'          => $p['title'],
        'featured_image' => $p['featured_image'],
        'category_name'  => $p['category_name'],
    ], $results);
    echo json_encode($out);
    exit;
}

$perPage    = (int)getSetting('posts_per_page', '6');
$total      = $query ? countSearchPosts($query) : 0;
$totalPages = $query ? max(1, (int)ceil($total / $perPage)) : 1;
$page       = min($page, $totalPages);
$results    = $query ? searchPosts($query, $page, $perPage) : [];

$pageTitle       = $query ? "Search: " . e($query) : 'Search';
$pageDescription = "Search results for \"$query\" on " . getSetting('site_name', 'ModernBlog');

require_once __DIR__ . '/includes/header.php';
?>

<!-- Search Hero -->
<div class="search-hero">
  <div class="container">
    <h1 style="font-size:1.75rem;font-weight:800;margin-bottom:1rem;">
      <i class="fas fa-search" style="color:var(--primary);"></i>
      <?= $query ? 'Results for "' . e($query) . '"' : 'Search Posts' ?>
    </h1>
    <form class="search-form" action="<?= BASE_URL ?>search.php" method="get" role="search">
      <input type="search"
             name="q"
             value="<?= e($query) ?>"
             placeholder="Search posts, categories…"
             aria-label="Search"
             class="live-search-input"
             required>
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-search"></i> Search
      </button>
    </form>
  </div>
</div>

<div class="container" style="padding:2rem 1.5rem 4rem;">

  <?php if ($query && empty($results)): ?>
  <div style="text-align:center;padding:4rem 1.5rem;color:var(--text-muted);">
    <i class="fas fa-search" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:.4;"></i>
    <h2 style="margin-bottom:.5rem;">No results found</h2>
    <p>Try different keywords or browse our categories below.</p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;margin-top:1.5rem;">
      <?php foreach ($categories as $cat): ?>
      <a href="<?= BASE_URL ?>category.php?slug=<?= e($cat['slug']) ?>" class="btn btn-secondary btn-sm">
        <?= e($cat['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <?php elseif ($query): ?>

  <div class="search-results-count">
    Found <strong><?= $total ?></strong> result<?= $total !== 1 ? 's' : '' ?> for "<strong><?= e($query) ?></strong>"
    <?php if ($totalPages > 1): ?> — Page <?= $page ?> of <?= $totalPages ?><?php endif; ?>
  </div>

  <div class="grid-auto" style="margin-bottom:2rem;">
    <?php foreach ($results as $post): ?>
    <article class="card">
      <?php if ($post['featured_image']): ?>
      <img src="<?= e($post['featured_image']) ?>" alt="<?= e($post['title']) ?>" class="card-img" loading="lazy">
      <?php endif; ?>
      <div class="card-body">
        <div class="card-meta">
          <?php if ($post['category_name']): ?>
          <a href="<?= BASE_URL ?>category.php?slug=<?= e($post['category_slug']) ?>" class="badge">
            <?= e($post['category_name']) ?>
          </a>
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
        <span><?= formatDate($post['created_at'], 'M j, Y') ?></span>
        <span class="card-stat"><i class="fas fa-eye"></i> <?= number_format($post['views']) ?></span>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <?= paginationLinks($page, $totalPages, BASE_URL . 'search.php?q=' . urlencode($query) . '&page=') ?>

  <?php else: ?>
  <!-- No query — show categories -->
  <div style="max-width:700px;margin:2rem auto;text-align:center;">
    <p style="color:var(--text-muted);margin-bottom:2rem;">Enter a search term above to find posts, or browse categories:</p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
      <?php foreach ($categories as $cat): ?>
      <a href="<?= BASE_URL ?>category.php?slug=<?= e($cat['slug']) ?>" class="card" style="padding:1.25rem 2rem;text-decoration:none;flex:0 0 auto;">
        <div style="font-weight:700;color:var(--text);"><?= e($cat['name']) ?></div>
        <div style="font-size:.8rem;color:var(--text-muted);"><?= (int)$cat['post_count'] ?> posts</div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
