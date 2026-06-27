<?php
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\') . '/');

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'admin/dashboard.php');
    exit;
}

$error    = '';
$redirect = $_GET['redirect'] ?? BASE_URL . 'admin/dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } elseif (loginAdmin($username, $password)) {
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — <?= e(getSetting('site_name', 'ModernBlog')) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
</head>
<body>

<div class="login-wrapper">
  <div class="login-card">

    <div class="login-logo">
      <div class="login-logo-text">
        <i class="fas fa-feather-alt"></i> <?= e(getSetting('site_name', 'ModernBlog')) ?>
      </div>
    </div>

    <h1 class="login-title">Welcome back</h1>
    <p class="login-sub">Sign in to access the admin panel</p>

    <?php if ($error): ?>
    <div class="alert alert-error">
      <i class="fas fa-exclamation-circle"></i> <?= e($error) ?>
    </div>
    <?php endif; ?>

    <form method="post" action="">
      <?= csrfField() ?>
      <input type="hidden" name="redirect" value="<?= e($redirect) ?>">

      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <div style="position:relative;">
          <i class="fas fa-user" style="position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.85rem;"></i>
          <input type="text"
                 id="username"
                 name="username"
                 class="form-control"
                 style="padding-left:2.5rem;"
                 value="<?= e($_POST['username'] ?? '') ?>"
                 required
                 autocomplete="username"
                 autofocus
                 placeholder="admin">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div style="position:relative;">
          <i class="fas fa-lock" style="position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.85rem;"></i>
          <input type="password"
                 id="password"
                 name="password"
                 class="form-control"
                 style="padding-left:2.5rem;padding-right:2.5rem;"
                 required
                 autocomplete="current-password"
                 placeholder="••••••••">
          <button type="button"
                  id="toggle-password"
                  style="position:absolute;right:.9rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:.85rem;"
                  aria-label="Toggle password visibility">
            <i class="fas fa-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:1.5rem;padding:.75rem;">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>
    </form>

    <div style="margin-top:1.5rem;text-align:center;font-size:.8rem;color:var(--text-light);">
      <a href="<?= BASE_URL ?>index.php" style="color:var(--text-muted);">
        <i class="fas fa-arrow-left"></i> Back to Blog
      </a>
    </div>

  </div>
</div>

<script>
document.getElementById('toggle-password')?.addEventListener('click', function() {
  const pwd = document.getElementById('password');
  const icon = this.querySelector('i');
  if (pwd.type === 'password') {
    pwd.type = 'text';
    icon.className = 'fas fa-eye-slash';
  } else {
    pwd.type = 'password';
    icon.className = 'fas fa-eye';
  }
});
</script>
</body>
</html>
