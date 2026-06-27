<?php
// Footer – expects $categories and getSetting() available
if (!isset($categories)) $categories = getCategories();
$siteName   = getSetting('site_name', 'ModernBlog');
$siteDesc   = getSetting('site_description', '');
$twitter    = getSetting('social_twitter', '');
$github     = getSetting('social_github', '');
$footerText = getSetting('footer_text', "© " . date('Y') . " ModernBlog. All rights reserved.");
?>
</main><!-- /#main-content -->

<!-- Footer -->
<footer class="footer" role="contentinfo">
  <div class="container">
    <div class="footer-grid">

      <!-- Brand -->
      <div class="footer-brand">
        <a href="<?= BASE_URL ?>index.php" class="nav-logo"><i class="fas fa-feather-alt"></i> <?= e($siteName) ?></a>
        <p><?= e($siteDesc) ?></p>
        <div class="footer-social">
          <?php if ($twitter): ?>
          <a href="<?= e($twitter) ?>" target="_blank" rel="noopener noreferrer" aria-label="Twitter">
            <i class="fab fa-twitter"></i>
          </a>
          <?php endif; ?>
          <?php if ($github): ?>
          <a href="<?= e($github) ?>" target="_blank" rel="noopener noreferrer" aria-label="GitHub">
            <i class="fab fa-github"></i>
          </a>
          <?php endif; ?>
          <a href="<?= BASE_URL ?>feed.php" aria-label="RSS Feed">
            <i class="fas fa-rss"></i>
          </a>
        </div>
      </div>

      <!-- Categories -->
      <div class="footer-col">
        <h4>Categories</h4>
        <ul>
          <?php foreach ($categories as $cat): ?>
          <li>
            <a href="<?= BASE_URL ?>category.php?slug=<?= e($cat['slug']) ?>">
              <?= e($cat['name']) ?>
              <small style="color:var(--text-light);">(<?= (int)$cat['post_count'] ?>)</small>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Quick Links -->
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="<?= BASE_URL ?>index.php">Home</a></li>
          <li><a href="<?= BASE_URL ?>about.php">About</a></li>
          <li><a href="<?= BASE_URL ?>contact.php">Contact</a></li>
          <li><a href="<?= BASE_URL ?>search.php">Search</a></li>
          <li><a href="<?= BASE_URL ?>feed.php">RSS Feed</a></li>
        </ul>
      </div>

      <!-- Newsletter / Info -->
      <div class="footer-col">
        <h4>Newsletter</h4>
        <p style="font-size:.875rem;color:var(--text-muted);margin-bottom:.75rem;">
          Stay updated with our latest articles.
        </p>
        <a href="<?= BASE_URL ?>feed.php" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:.5rem;margin-bottom:.75rem;">
          <i class="fas fa-rss"></i> Subscribe via RSS
        </a>
        <p style="font-size:.8rem;color:var(--text-muted);">
          Or <a href="<?= BASE_URL ?>contact.php" style="color:var(--primary);">contact us</a> to get in touch.
        </p>
      </div>

    </div>

    <!-- Footer bottom -->
    <div class="footer-bottom">
      <span><?= e($footerText) ?></span>
      <span>Built with <i class="fas fa-heart" style="color:#ef4444;"></i> using PHP & MySQL</span>
    </div>
  </div>
</footer>

<!-- Back to Top -->
<button id="back-to-top" aria-label="Back to top" title="Back to top">
  <i class="fas fa-arrow-up"></i>
</button>

<!-- Prism.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js" crossorigin="anonymous" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js" crossorigin="anonymous" defer></script>

<!-- Main JS -->
<script src="<?= BASE_URL ?>assets/js/main.js" defer></script>
</body>
</html>
