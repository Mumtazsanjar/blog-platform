<?php
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\') . '/');

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
$admin = getCurrentAdmin();
$pdo   = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $tab = $_POST['tab'] ?? 'general';

    if ($tab === 'general') {
        $fields = ['site_name','site_description','site_email','posts_per_page','footer_text','allow_comments'];
        foreach ($fields as $k) {
            $v = trim($_POST[$k] ?? '');
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
            $stmt->execute([$k, $v, $v]);
        }
        setFlash('success', 'General settings saved.');
    } elseif ($tab === 'social') {
        $fields = ['social_twitter','social_github','social_facebook','social_linkedin'];
        foreach ($fields as $k) {
            $v = trim($_POST[$k] ?? '');
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
            $stmt->execute([$k, $v, $v]);
        }
        setFlash('success', 'Social settings saved.');
    } elseif ($tab === 'password') {
        $currentPwd = $_POST['current_password'] ?? '';
        $newPwd     = $_POST['new_password']     ?? '';
        $confirmPwd = $_POST['confirm_password'] ?? '';

        $userStmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
        $userStmt->execute([$admin['id']]);
        $user = $userStmt->fetch();

        if (!password_verify($currentPwd, $user['password'])) {
            setFlash('error', 'Current password is incorrect.');
        } elseif (strlen($newPwd) < 8) {
            setFlash('error', 'New password must be at least 8 characters.');
        } elseif ($newPwd !== $confirmPwd) {
            setFlash('error', 'Passwords do not match.');
        } else {
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([hashPassword($newPwd), $admin['id']]);
            setFlash('success', 'Password changed successfully.');
        }
    }
    header('Location: ' . BASE_URL . 'admin/settings.php?tab=' . $tab);
    exit;
}

// Load all settings
$settingsRaw = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
$settings    = array_column($settingsRaw, 'setting_value', 'setting_key');

$activeTab       = $_GET['tab'] ?? 'general';
$pendingComments = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE approved=0")->fetchColumn();
$pageTitle       = 'Settings';
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

      <!-- Tab nav -->
      <div style="display:flex;gap:.5rem;margin-bottom:1.5rem;border-bottom:1px solid var(--border);padding-bottom:0;">
        <?php $tabs = ['general'=>'General','social'=>'Social','password'=>'Password']; ?>
        <?php foreach ($tabs as $key=>$label): ?>
        <a href="?tab=<?= $key ?>"
           style="padding:.6rem 1.25rem;font-size:.875rem;font-weight:600;border-bottom:2px solid <?= $activeTab===$key ? 'var(--primary)':'transparent' ?>;color:<?= $activeTab===$key ? 'var(--primary)':'var(--text-muted)' ?>;transition:color .2s;margin-bottom:-1px;">
          <?= $label ?>
        </a>
        <?php endforeach; ?>
      </div>

      <div style="max-width:640px;">

        <?php if ($activeTab === 'general'): ?>
        <div class="admin-card">
          <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="fas fa-cog"></i> General Settings</h3>
          </div>
          <div class="admin-card-body">
            <form method="post">
              <?= csrfField() ?>
              <input type="hidden" name="tab" value="general">
              <div class="form-group">
                <label class="form-label" for="site_name">Site Name *</label>
                <input type="text" id="site_name" name="site_name" class="form-control" value="<?= e($settings['site_name'] ?? 'ModernBlog') ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label" for="site_description">Site Description</label>
                <textarea id="site_description" name="site_description" class="form-control" rows="2"><?= e($settings['site_description'] ?? '') ?></textarea>
              </div>
              <div class="form-group">
                <label class="form-label" for="site_email">Contact Email</label>
                <input type="email" id="site_email" name="site_email" class="form-control" value="<?= e($settings['site_email'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label" for="posts_per_page">Posts Per Page</label>
                <input type="number" id="posts_per_page" name="posts_per_page" class="form-control" value="<?= e($settings['posts_per_page'] ?? '6') ?>" min="1" max="50" style="width:100px;">
              </div>
              <div class="form-group">
                <label class="form-label" for="footer_text">Footer Text</label>
                <input type="text" id="footer_text" name="footer_text" class="form-control" value="<?= e($settings['footer_text'] ?? '') ?>">
              </div>
              <div class="toggle-group" style="margin-bottom:1.5rem;">
                <label class="toggle">
                  <input type="checkbox" name="allow_comments" <?= ($settings['allow_comments'] ?? '1') === '1' ? 'checked' : '' ?>>
                  <span class="toggle-slider"></span>
                </label>
                <span class="toggle-label">Allow Comments on Posts</span>
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
            </form>
          </div>
        </div>

        <?php elseif ($activeTab === 'social'): ?>
        <div class="admin-card">
          <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="fas fa-share-alt"></i> Social Media</h3>
          </div>
          <div class="admin-card-body">
            <form method="post">
              <?= csrfField() ?>
              <input type="hidden" name="tab" value="social">
              <?php $socials = [
                'social_twitter'  => ['Twitter / X',  'fab fa-twitter',   'https://twitter.com/yourhandle'],
                'social_github'   => ['GitHub',        'fab fa-github',    'https://github.com/youruser'],
                'social_facebook' => ['Facebook',      'fab fa-facebook',  'https://facebook.com/yourpage'],
                'social_linkedin' => ['LinkedIn',      'fab fa-linkedin',  'https://linkedin.com/in/yourprofile'],
              ]; ?>
              <?php foreach ($socials as $key => [$label, $icon, $placeholder]): ?>
              <div class="form-group">
                <label class="form-label" for="<?= $key ?>">
                  <i class="<?= $icon ?>"></i> <?= $label ?>
                </label>
                <input type="url" id="<?= $key ?>" name="<?= $key ?>" class="form-control"
                       value="<?= e($settings[$key] ?? '') ?>"
                       placeholder="<?= $placeholder ?>">
              </div>
              <?php endforeach; ?>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Social Settings</button>
            </form>
          </div>
        </div>

        <?php elseif ($activeTab === 'password'): ?>
        <div class="admin-card">
          <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="fas fa-lock"></i> Change Password</h3>
          </div>
          <div class="admin-card-body">
            <form method="post">
              <?= csrfField() ?>
              <input type="hidden" name="tab" value="password">
              <div class="form-group">
                <label class="form-label" for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="form-control" required autocomplete="current-password">
              </div>
              <div class="form-group">
                <label class="form-label" for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" class="form-control" required autocomplete="new-password" minlength="8">
                <p class="form-hint">Minimum 8 characters.</p>
              </div>
              <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required autocomplete="new-password">
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-lock"></i> Change Password</button>
            </form>
          </div>
        </div>
        <?php endif; ?>

      </div>

    </main>
  </div>
</div>
<script src="<?= BASE_URL ?>assets/js/admin.js" defer></script>
</body>
</html>
