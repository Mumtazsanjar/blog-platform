<?php
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\') . '/');

require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$success = false;
$errors  = [];
$values  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $values['name']    = trim($_POST['name']    ?? '');
        $values['email']   = trim($_POST['email']   ?? '');
        $values['subject'] = trim($_POST['subject'] ?? '');
        $values['message'] = trim($_POST['message'] ?? '');

        if (empty($values['name']))    $errors[] = 'Your name is required.';
        if (empty($values['email']) || !filter_var($values['email'], FILTER_VALIDATE_EMAIL))
                                       $errors[] = 'A valid email address is required.';
        if (empty($values['subject'])) $errors[] = 'Subject is required.';
        if (empty($values['message'])) $errors[] = 'Message is required.';
        if (strlen($values['message']) < 10) $errors[] = 'Message is too short.';

        if (empty($errors)) {
            $pdo  = getDB();
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?,?,?,?)");
            $stmt->execute([$values['name'], $values['email'], $values['subject'], $values['message']]);
            $success = true;
            $values  = [];
        }
    }
}

$pageTitle       = 'Contact Us — ' . getSetting('site_name', 'ModernBlog');
$pageDescription = 'Get in touch with the ' . getSetting('site_name', 'ModernBlog') . ' team.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<div class="page-hero">
  <div class="container">
    <h1>Get in Touch</h1>
    <p>Have a question, suggestion, or just want to say hello? We'd love to hear from you.</p>
  </div>
</div>

<div class="container" style="padding:4rem 1.5rem;">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:start;" class="contact-grid">

    <!-- Form -->
    <div>
      <h2 style="font-size:1.5rem;font-weight:800;margin-bottom:1.5rem;">Send a Message</h2>

      <?php if ($success): ?>
      <div class="alert alert-success" data-auto-dismiss="6000">
        <i class="fas fa-check-circle"></i>
        <div>
          <strong>Message sent!</strong><br>
          Thank you for reaching out. We'll get back to you as soon as possible.
        </div>
      </div>
      <?php endif; ?>

      <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= e($err) ?></div>
      <?php endforeach; ?>

      <form id="contact-form" method="post" novalidate>
        <?= csrfField() ?>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="name">Your Name *</label>
            <input type="text"
                   id="name"
                   name="name"
                   class="form-control"
                   value="<?= e($values['name'] ?? '') ?>"
                   required maxlength="80"
                   placeholder="John Doe">
          </div>
          <div class="form-group">
            <label class="form-label" for="email">Email Address *</label>
            <input type="email"
                   id="email"
                   name="email"
                   class="form-control"
                   value="<?= e($values['email'] ?? '') ?>"
                   required maxlength="120"
                   placeholder="john@example.com">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="subject">Subject *</label>
          <input type="text"
                 id="subject"
                 name="subject"
                 class="form-control"
                 value="<?= e($values['subject'] ?? '') ?>"
                 required maxlength="200"
                 placeholder="What's this about?">
        </div>

        <div class="form-group">
          <label class="form-label" for="message">Message *</label>
          <textarea id="message"
                    name="message"
                    class="form-control"
                    rows="6"
                    required
                    minlength="10"
                    placeholder="Your message here…"><?= e($values['message'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">
          <i class="fas fa-paper-plane"></i> Send Message
        </button>
      </form>
    </div>

    <!-- Info -->
    <div>
      <h2 style="font-size:1.5rem;font-weight:800;margin-bottom:1.5rem;">Contact Information</h2>

      <div style="display:flex;flex-direction:column;gap:1.5rem;">

        <div style="display:flex;gap:1rem;align-items:flex-start;">
          <div style="width:48px;height:48px;background:rgba(99,102,241,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:1.1rem;flex-shrink:0;">
            <i class="fas fa-envelope"></i>
          </div>
          <div>
            <h4 style="font-weight:700;margin-bottom:.2rem;">Email</h4>
            <a href="mailto:<?= e(getSetting('site_email', 'contact@modernblog.com')) ?>" style="color:var(--text-muted);">
              <?= e(getSetting('site_email', 'contact@modernblog.com')) ?>
            </a>
          </div>
        </div>

        <?php if (getSetting('social_twitter')): ?>
        <div style="display:flex;gap:1rem;align-items:flex-start;">
          <div style="width:48px;height:48px;background:rgba(29,155,240,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#1d9bf0;font-size:1.1rem;flex-shrink:0;">
            <i class="fab fa-twitter"></i>
          </div>
          <div>
            <h4 style="font-weight:700;margin-bottom:.2rem;">Twitter / X</h4>
            <a href="<?= e(getSetting('social_twitter')) ?>" target="_blank" rel="noopener" style="color:var(--text-muted);">
              Follow us on Twitter
            </a>
          </div>
        </div>
        <?php endif; ?>

        <?php if (getSetting('social_github')): ?>
        <div style="display:flex;gap:1rem;align-items:flex-start;">
          <div style="width:48px;height:48px;background:rgba(139,92,246,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--secondary);font-size:1.1rem;flex-shrink:0;">
            <i class="fab fa-github"></i>
          </div>
          <div>
            <h4 style="font-weight:700;margin-bottom:.2rem;">GitHub</h4>
            <a href="<?= e(getSetting('social_github')) ?>" target="_blank" rel="noopener" style="color:var(--text-muted);">
              Check out our code
            </a>
          </div>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:1rem;align-items:flex-start;">
          <div style="width:48px;height:48px;background:rgba(239,68,68,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#ef4444;font-size:1.1rem;flex-shrink:0;">
            <i class="fas fa-rss"></i>
          </div>
          <div>
            <h4 style="font-weight:700;margin-bottom:.2rem;">RSS Feed</h4>
            <a href="<?= BASE_URL ?>feed.php" style="color:var(--text-muted);">Subscribe to our RSS feed</a>
          </div>
        </div>

      </div>

      <!-- FAQ -->
      <div style="margin-top:3rem;padding:1.75rem;background:var(--bg-secondary);border-radius:var(--radius-lg);border:1px solid var(--border);">
        <h3 style="font-weight:700;margin-bottom:1.25rem;font-size:1.1rem;">
          <i class="fas fa-question-circle" style="color:var(--primary);"></i> Frequently Asked
        </h3>
        <div style="display:flex;flex-direction:column;gap:1rem;">
          <div>
            <p style="font-weight:600;margin-bottom:.3rem;font-size:.9rem;">Can I contribute an article?</p>
            <p style="color:var(--text-muted);font-size:.85rem;">Absolutely! Send us your idea via the contact form and we'll get back to you.</p>
          </div>
          <div>
            <p style="font-weight:600;margin-bottom:.3rem;font-size:.9rem;">How long does a response take?</p>
            <p style="color:var(--text-muted);font-size:.85rem;">We typically respond within 1–3 business days.</p>
          </div>
          <div>
            <p style="font-weight:600;margin-bottom:.3rem;font-size:.9rem;">Can I republish your articles?</p>
            <p style="color:var(--text-muted);font-size:.85rem;">Please reach out first — we're happy to discuss syndication and attribution.</p>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<style>
@media(max-width:768px) { .contact-grid { grid-template-columns:1fr !important; } }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
