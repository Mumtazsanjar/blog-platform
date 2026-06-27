<?php
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\') . '/');

require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
$totalPosts    = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
$totalComments = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE approved=1")->fetchColumn();
$totalViews    = (int)$pdo->query("SELECT COALESCE(SUM(views),0) FROM posts")->fetchColumn();
$totalLikes    = (int)$pdo->query("SELECT COUNT(*) FROM likes")->fetchColumn();

$pageTitle       = 'About Us — ' . getSetting('site_name', 'ModernBlog');
$pageDescription = 'Learn more about ' . getSetting('site_name', 'ModernBlog') . ' and our mission to share quality tech content.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<div class="page-hero">
  <div class="container">
    <h1><?= e(getSetting('site_name', 'ModernBlog')) ?></h1>
    <p>Where ideas, code, and creativity come together</p>
  </div>
</div>

<div class="container" style="padding:4rem 1.5rem;">

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-number"><?= number_format($totalPosts) ?>+</div>
      <div class="stat-label">Articles Published</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?= number_format($totalViews) ?>+</div>
      <div class="stat-label">Total Reads</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?= number_format($totalComments) ?>+</div>
      <div class="stat-label">Comments</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?= number_format($totalLikes) ?>+</div>
      <div class="stat-label">Likes</div>
    </div>
  </div>

  <!-- Mission -->
  <div class="about-grid" style="margin:4rem 0;">
    <div>
      <span class="badge" style="margin-bottom:1rem;display:inline-flex;">Our Mission</span>
      <h2 style="font-size:2rem;font-weight:800;margin-bottom:1rem;line-height:1.25;">
        Empowering developers to build better things
      </h2>
      <p style="color:var(--text-muted);font-size:1.05rem;line-height:1.8;margin-bottom:1rem;">
        <?= e(getSetting('site_name', 'ModernBlog')) ?> is a community-driven platform dedicated to sharing practical knowledge about web development, design, and technology. We believe that quality content, presented clearly, can transform how developers approach their craft.
      </p>
      <p style="color:var(--text-muted);font-size:1.05rem;line-height:1.8;margin-bottom:1.5rem;">
        Whether you're a seasoned engineer or just starting your coding journey, our articles are written to be approachable, thorough, and immediately actionable.
      </p>
      <div style="display:flex;gap:1rem;flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>index.php" class="btn btn-primary">
          <i class="fas fa-book-open"></i> Read Articles
        </a>
        <a href="<?= BASE_URL ?>contact.php" class="btn btn-secondary">
          <i class="fas fa-envelope"></i> Get in Touch
        </a>
      </div>
    </div>
    <div>
      <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=600&q=80"
           alt="Team working together"
           class="about-img"
           loading="lazy">
    </div>
  </div>

  <!-- What we cover -->
  <div style="margin:4rem 0;">
    <h2 style="font-size:1.75rem;font-weight:800;margin-bottom:.5rem;text-align:center;">What We Cover</h2>
    <p style="text-align:center;color:var(--text-muted);margin-bottom:2.5rem;">Practical content across the full spectrum of modern web development</p>
    <div class="grid-3">
      <?php
      $topics = [
        ['fas fa-code',          'Web Development',   'Deep dives into PHP, JavaScript, HTML, CSS and modern frameworks.', 'var(--primary)'],
        ['fas fa-palette',       'UI/UX Design',      'Principles, patterns, and practical design techniques.', '#8b5cf6'],
        ['fas fa-server',        'Backend & APIs',    'Database design, REST APIs, authentication, and server-side architecture.', '#06b6d4'],
        ['fas fa-mobile-alt',    'Responsive Design', 'Mobile-first development, CSS Grid, Flexbox, and beyond.', '#10b981'],
        ['fas fa-shield-alt',    'Security',          'Best practices for securing web applications and protecting user data.', '#f59e0b'],
        ['fas fa-rocket',        'Performance',       'Optimization strategies, caching, and making the web faster.', '#ef4444'],
      ];
      foreach ($topics as [$icon, $title, $desc, $color]):
      ?>
      <div class="card" style="padding:1.75rem;text-align:center;">
        <div style="width:60px;height:60px;border-radius:50%;background:<?= $color ?>22;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.5rem;color:<?= $color ?>;">
          <i class="<?= $icon ?>"></i>
        </div>
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:.5rem;"><?= $title ?></h3>
        <p style="font-size:.875rem;color:var(--text-muted);"><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Team / Author -->
  <div style="margin:4rem 0;text-align:center;">
    <h2 style="font-size:1.75rem;font-weight:800;margin-bottom:.5rem;">The Author</h2>
    <p style="color:var(--text-muted);margin-bottom:2.5rem;">The person behind the content</p>
    <div style="display:flex;justify-content:center;">
      <div class="card" style="max-width:300px;padding:2rem;text-align:center;">
        <div style="width:80px;height:80px;border-radius:50%;background:var(--gradient);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:2rem;font-weight:800;color:#fff;">A</div>
        <h3 style="font-weight:700;margin-bottom:.25rem;">Admin User</h3>
        <p style="font-size:.8rem;color:var(--primary);margin-bottom:.75rem;">Platform Administrator</p>
        <p style="font-size:.875rem;color:var(--text-muted);">Platform administrator and chief editor of <?= e(getSetting('site_name', 'ModernBlog')) ?>.</p>
      </div>
    </div>
  </div>

  <!-- CTA -->
  <div style="text-align:center;padding:3rem;background:var(--gradient);border-radius:var(--radius-lg);color:#fff;margin:4rem 0;">
    <h2 style="font-size:2rem;font-weight:800;margin-bottom:.75rem;">Ready to dive in?</h2>
    <p style="opacity:.88;margin-bottom:2rem;font-size:1.05rem;">Explore our latest articles and level up your development skills.</p>
    <a href="<?= BASE_URL ?>index.php" class="btn btn-white btn-lg">
      <i class="fas fa-book-open"></i> Browse All Posts
    </a>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
