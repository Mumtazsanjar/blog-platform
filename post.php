<?php
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\') . '/');

require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$post = getPostBySlug($slug);
if (!$post) {
    http_response_code(404);
    $pageTitle = '404 — Post Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container" style="padding:4rem 1.5rem;text-align:center;">
        <i class="fas fa-exclamation-triangle" style="font-size:3rem;color:var(--primary);"></i>
        <h1 style="margin:1rem 0 .5rem;">Post Not Found</h1>
        <p style="color:var(--text-muted);">The post you\'re looking for doesn\'t exist or has been removed.</p>
        <a href="' . BASE_URL . 'index.php" class="btn btn-primary" style="margin-top:1.5rem;">Go Home</a>
    </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Increment views (once per session per post)
$viewKey = 'viewed_post_' . $post['id'];
if (empty($_SESSION[$viewKey])) {
    incrementViews($post['id']);
    $_SESSION[$viewKey] = true;
    $post['views']++;
}

$tags         = getPostTags($post['id']);
$comments     = getComments($post['id']);
$relatedPosts = getRelatedPosts($post['id'], $post['category_id'] ?? null, 3);
$likeCount    = getLikeCount($post['id']);
$userLiked    = hasLiked($post['id']);

$pageTitle       = $post['title'];
$pageDescription = $post['excerpt'] ? truncate($post['excerpt'], 160) : truncate($post['content'], 160);
$ogImage         = $post['featured_image'] ?? '';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <div class="post-layout">

    <!-- Article -->
    <article>

      <!-- Breadcrumb -->
      <nav aria-label="Breadcrumb" style="margin-bottom:1.5rem;font-size:.85rem;color:var(--text-muted);">
        <a href="<?= BASE_URL ?>index.php">Home</a>
        <?php if ($post['category_name']): ?>
        <span style="margin:0 .4rem;">/</span>
        <a href="<?= BASE_URL ?>category.php?slug=<?= e($post['category_slug']) ?>"><?= e($post['category_name']) ?></a>
        <?php endif; ?>
        <span style="margin:0 .4rem;">/</span>
        <span style="color:var(--text);"><?= e(truncate($post['title'], 50)) ?></span>
      </nav>

      <!-- Post Header -->
      <header class="post-header">
        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem;">
          <?php if ($post['category_name']): ?>
          <a href="<?= BASE_URL ?>category.php?slug=<?= e($post['category_slug']) ?>" class="badge">
            <?= e($post['category_name']) ?>
          </a>
          <?php endif; ?>
          <?php if ($post['featured']): ?>
          <span class="badge badge-warning"><i class="fas fa-star"></i> Featured</span>
          <?php endif; ?>
        </div>

        <h1 class="post-title"><?= e($post['title']) ?></h1>

        <div class="post-meta-row">
          <span><i class="fas fa-user-circle" style="color:var(--primary);"></i> <?= e($post['author_name']) ?></span>
          <span><i class="fas fa-calendar-alt"></i> <?= formatDate($post['created_at'], 'F j, Y') ?></span>
          <span><i class="fas fa-clock"></i> <span id="reading-time"><?= readingTime($post['content']) ?> min read</span></span>
          <span><i class="fas fa-eye"></i> <?= number_format($post['views']) ?> views</span>
          <span><i class="fas fa-comment"></i> <?= count($comments) ?> comments</span>
        </div>
      </header>

      <!-- Featured Image -->
      <?php if ($post['featured_image']): ?>
      <img src="<?= e($post['featured_image']) ?>"
           alt="<?= e($post['title']) ?>"
           class="post-featured-img"
           loading="eager">
      <?php endif; ?>

      <!-- Table of Contents -->
      <div id="toc-container"></div>

      <!-- Content -->
      <div class="prose">
        <?= $post['content'] ?>
      </div>

      <!-- Tags -->
      <?php if (!empty($tags)): ?>
      <div style="margin-top:2rem;display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
        <span style="font-weight:600;font-size:.875rem;color:var(--text-muted);">
          <i class="fas fa-tag"></i> Tags:
        </span>
        <?php foreach ($tags as $tag): ?>
        <a href="<?= BASE_URL ?>search.php?q=<?= e($tag['name']) ?>" class="tag"><?= e($tag['name']) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Like & Share -->
      <div style="margin-top:2.5rem;padding:1.5rem;background:var(--bg-secondary);border-radius:var(--radius-lg);border:1px solid var(--border);">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1.5rem;">
          <div>
            <p style="font-weight:600;margin-bottom:.75rem;">Did you enjoy this article?</p>
            <button class="btn-like <?= $userLiked ? 'liked' : '' ?>" data-post-id="<?= $post['id'] ?>">
              <i class="<?= $userLiked ? 'fas' : 'far' ?> fa-heart"></i>
              <span class="like-count"><?= $likeCount ?></span> Likes
            </button>
          </div>
          <div>
            <p style="font-weight:600;margin-bottom:.75rem;">Share this post:</p>
            <div class="share-buttons">
              <button class="share-btn share-twitter" data-share="twitter">
                <i class="fab fa-twitter"></i> Twitter
              </button>
              <button class="share-btn share-facebook" data-share="facebook">
                <i class="fab fa-facebook-f"></i> Facebook
              </button>
              <button class="share-btn share-copy" data-share="copy">
                <i class="fas fa-link"></i> Copy Link
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Author Bio -->
      <div style="margin-top:2.5rem;padding:1.5rem;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-lg);display:flex;gap:1.25rem;align-items:flex-start;">
        <div style="width:64px;height:64px;border-radius:50%;background:var(--gradient);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1.5rem;flex-shrink:0;">
          <?= strtoupper(substr($post['author_name'], 0, 1)) ?>
        </div>
        <div>
          <p style="font-weight:700;margin-bottom:.25rem;"><?= e($post['author_name']) ?></p>
          <p style="font-size:.875rem;color:var(--text-muted);">
            Writer and contributor at <?= e(getSetting('site_name', 'ModernBlog')) ?>.
          </p>
        </div>
      </div>

      <!-- Navigation (prev / next) -->
      <?php
      $pdo = getDB();
      $prevPost = $pdo->prepare("SELECT slug, title FROM posts WHERE status='published' AND created_at < ? ORDER BY created_at DESC LIMIT 1");
      $prevPost->execute([$post['created_at']]);
      $prev = $prevPost->fetch();

      $nextPost = $pdo->prepare("SELECT slug, title FROM posts WHERE status='published' AND created_at > ? ORDER BY created_at ASC LIMIT 1");
      $nextPost->execute([$post['created_at']]);
      $next = $nextPost->fetch();
      ?>
      <?php if ($prev || $next): ?>
      <div style="margin-top:2.5rem;display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
        <?php if ($prev): ?>
        <a href="<?= BASE_URL ?>post.php?slug=<?= e($prev['slug']) ?>"
           style="padding:1rem;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-md);transition:all .2s;">
          <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:.4rem;"><i class="fas fa-arrow-left"></i> Previous</div>
          <div style="font-weight:600;font-size:.875rem;"><?= e(truncate($prev['title'], 60)) ?></div>
        </a>
        <?php else: ?><div></div><?php endif; ?>

        <?php if ($next): ?>
        <a href="<?= BASE_URL ?>post.php?slug=<?= e($next['slug']) ?>"
           style="padding:1rem;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-md);text-align:right;transition:all .2s;">
          <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:.4rem;">Next <i class="fas fa-arrow-right"></i></div>
          <div style="font-weight:600;font-size:.875rem;"><?= e(truncate($next['title'], 60)) ?></div>
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Comments Section -->
      <section class="comments-section" id="comments" aria-label="Comments">
        <h2 class="comments-title">
          <i class="fas fa-comments" style="color:var(--primary);"></i>
          <?= count($comments) ?> Comment<?= count($comments) !== 1 ? 's' : '' ?>
        </h2>

        <?php if (empty($comments)): ?>
        <p style="color:var(--text-muted);padding:1.5rem 0;">No comments yet. Be the first to share your thoughts!</p>
        <?php else: ?>
        <div class="comments-list">
          <?php foreach ($comments as $comment): ?>
          <div class="comment-item">
            <div class="comment-avatar" aria-hidden="true">
              <?= strtoupper(substr($comment['name'], 0, 1)) ?>
            </div>
            <div>
              <div>
                <span class="comment-name"><?= e($comment['name']) ?></span>
                <span class="comment-date"><?= timeAgo($comment['created_at']) ?></span>
              </div>
              <div class="comment-body"><?= e($comment['content']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (getSetting('allow_comments', '1') === '1'): ?>
        <!-- Comment form -->
        <div class="comment-form-wrapper" id="comment-form-section">
          <h3><i class="fas fa-pen"></i> Leave a Comment</h3>
          <div id="comment-status" class="alert" style="display:none;"></div>
          <form id="comment-form" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label" for="comment-name">Name <span style="color:var(--danger);">*</span></label>
                <input type="text" id="comment-name" name="name" class="form-control" required maxlength="80">
              </div>
              <div class="form-group">
                <label class="form-label" for="comment-email">Email <span style="color:var(--danger);">*</span></label>
                <input type="email" id="comment-email" name="email" class="form-control" required maxlength="120">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label" for="comment-content">Comment <span style="color:var(--danger);">*</span></label>
              <textarea id="comment-content" name="content" class="form-control" rows="4" required maxlength="2000"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-paper-plane"></i> Post Comment
            </button>
          </form>
        </div>
        <?php endif; ?>
      </section>

    </article>

    <!-- Sidebar -->
    <aside class="sidebar">

      <!-- Related Posts -->
      <?php if (!empty($relatedPosts)): ?>
      <div class="sidebar-widget">
        <h3 class="widget-title"><i class="fas fa-link"></i> Related Posts</h3>
        <?php foreach ($relatedPosts as $rp): ?>
        <div class="widget-post">
          <?php if ($rp['featured_image']): ?>
          <img src="<?= e($rp['featured_image']) ?>" alt="<?= e($rp['title']) ?>" loading="lazy">
          <?php endif; ?>
          <div>
            <a href="<?= BASE_URL ?>post.php?slug=<?= e($rp['slug']) ?>" class="widget-post-title">
              <?= e($rp['title']) ?>
            </a>
            <div class="widget-post-date"><?= formatDate($rp['created_at'], 'M j, Y') ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Categories -->
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

      <!-- Popular Posts -->
      <?php $popularSide = getPopularPosts(4); if (!empty($popularSide)): ?>
      <div class="sidebar-widget">
        <h3 class="widget-title"><i class="fas fa-fire"></i> Popular</h3>
        <?php foreach ($popularSide as $i => $pp): ?>
        <div class="widget-post" style="align-items:center;">
          <div style="width:28px;height:28px;background:var(--gradient);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.8rem;flex-shrink:0;">
            <?= $i + 1 ?>
          </div>
          <div>
            <a href="<?= BASE_URL ?>post.php?slug=<?= e($pp['slug']) ?>" class="widget-post-title">
              <?= e($pp['title']) ?>
            </a>
            <div class="widget-post-date"><i class="fas fa-eye"></i> <?= number_format($pp['views']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Share Widget -->
      <div class="sidebar-widget">
        <h3 class="widget-title"><i class="fas fa-share-alt"></i> Share</h3>
        <div class="share-buttons" style="flex-direction:column;gap:.5rem;">
          <button class="share-btn share-twitter" data-share="twitter" style="width:100%;justify-content:center;">
            <i class="fab fa-twitter"></i> Share on Twitter
          </button>
          <button class="share-btn share-facebook" data-share="facebook" style="width:100%;justify-content:center;">
            <i class="fab fa-facebook-f"></i> Share on Facebook
          </button>
          <button class="share-btn share-copy" data-share="copy" style="width:100%;justify-content:center;">
            <i class="fas fa-link"></i> Copy Link
          </button>
        </div>
      </div>

    </aside>

  </div><!-- /post-layout -->
</div><!-- /container -->

<!-- Related posts below -->
<?php if (!empty($relatedPosts)): ?>
<section style="background:var(--bg-secondary);padding:3rem 0;border-top:1px solid var(--border);">
  <div class="container">
    <div class="section-header">
      <div class="section-header-text">
        <h2>You Might Also Like</h2>
      </div>
    </div>
    <div class="grid-3">
      <?php foreach ($relatedPosts as $rp): ?>
      <article class="card">
        <?php if ($rp['featured_image']): ?>
        <img src="<?= e($rp['featured_image']) ?>" alt="<?= e($rp['title']) ?>" class="card-img" loading="lazy">
        <?php endif; ?>
        <div class="card-body">
          <?php if ($rp['category_name']): ?>
          <a href="<?= BASE_URL ?>category.php?slug=<?= e($rp['category_slug']) ?>" class="badge" style="margin-bottom:.5rem;display:inline-flex;"><?= e($rp['category_name']) ?></a>
          <?php endif; ?>
          <h3 class="card-title"><a href="<?= BASE_URL ?>post.php?slug=<?= e($rp['slug']) ?>"><?= e($rp['title']) ?></a></h3>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
