<?php
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\') . '/');

require_once __DIR__ . '/includes/functions.php';

http_response_code(404);
$pageTitle       = '404 — Page Not Found';
$pageDescription = 'The page you are looking for could not be found.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding:6rem 1.5rem;text-align:center;min-height:60vh;display:flex;flex-direction:column;align-items:center;justify-content:center;">
  <div style="font-size:8rem;font-weight:900;background:var(--gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1;margin-bottom:1.5rem;">
    404
  </div>
  <h1 style="font-size:2rem;font-weight:800;margin-bottom:.75rem;">Page Not Found</h1>
  <p style="color:var(--text-muted);font-size:1.05rem;max-width:480px;margin-bottom:2.5rem;">
    The page you're looking for doesn't exist or has been moved. Try searching for what you need.
  </p>
  <div style="display:flex;gap:1rem;flex-wrap:wrap;justify-content:center;">
    <a href="<?= BASE_URL ?>index.php" class="btn btn-primary">
      <i class="fas fa-home"></i> Go Home
    </a>
    <a href="<?= BASE_URL ?>search.php" class="btn btn-secondary">
      <i class="fas fa-search"></i> Search Posts
    </a>
  </div>

  <div style="margin-top:4rem;">
    <p style="font-size:.875rem;color:var(--text-muted);margin-bottom:1.5rem;">Or browse categories:</p>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;justify-content:center;">
      <?php foreach ($categories as $cat): ?>
      <a href="<?= BASE_URL ?>category.php?slug=<?= e($cat['slug']) ?>" class="btn btn-ghost btn-sm">
        <?= e($cat['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
