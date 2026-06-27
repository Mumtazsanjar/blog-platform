<?php
// Expects $admin, BASE_URL, pendingComments to be set in parent file
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentFile = basename($_SERVER['PHP_SELF']);
$pendingCnt  = isset($pendingComments) ? (int)$pendingComments : 0;
if (!isset($unreadMessages)) {
    $pdo2 = getDB();
    $unreadMessages = (int)$pdo2->query("SELECT COUNT(*) FROM contact_messages WHERE read_status=0")->fetchColumn();
}
function navLink(string $href, string $currentFile, string $icon, string $label, int $badge = 0): string {
    $filename = basename(parse_url($href, PHP_URL_PATH));
    $active = ($currentFile === $filename) ? ' active' : '';
    $badgeHtml = $badge > 0 ? "<span class=\"badge-count\">$badge</span>" : '';
    return "<a href=\"{$href}\" class=\"sidebar-link{$active}\"><i class=\"{$icon}\"></i> {$label}{$badgeHtml}</a>";
}
?>
<aside class="admin-sidebar" id="admin-sidebar" role="navigation" aria-label="Admin navigation">
  <div class="sidebar-logo">
    <i class="fas fa-feather-alt"></i>
    ModernBlog
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-section-label">Main</div>
    <?= navLink(BASE_URL . 'admin/dashboard.php',   $currentFile, 'fas fa-tachometer-alt', 'Dashboard') ?>
    <?= navLink(BASE_URL . 'admin/posts.php',        $currentFile, 'fas fa-file-alt',        'Posts') ?>
    <?= navLink(BASE_URL . 'admin/new-post.php',     $currentFile, 'fas fa-plus-circle',     'New Post') ?>

    <div class="sidebar-section-label">Content</div>
    <?= navLink(BASE_URL . 'admin/categories.php',   $currentFile, 'fas fa-folder',          'Categories') ?>
    <?= navLink(BASE_URL . 'admin/comments.php',     $currentFile, 'fas fa-comments',        'Comments', $pendingCnt) ?>

    <?= navLink(BASE_URL . 'admin/messages.php',     $currentFile, 'fas fa-envelope',       'Messages', $unreadMessages) ?>

    <div class="sidebar-section-label">System</div>
    <?= navLink(BASE_URL . 'admin/settings.php',     $currentFile, 'fas fa-cog',             'Settings') ?>

    <div class="sidebar-section-label">Site</div>
    <a href="<?= BASE_URL ?>index.php" class="sidebar-link" target="_blank">
      <i class="fas fa-external-link-alt"></i> View Blog
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?= strtoupper(substr($admin['username'], 0, 1)) ?></div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= e($admin['display_name'] ?: $admin['username']) ?></div>
        <div class="sidebar-user-role"><?= ucfirst($admin['role']) ?></div>
      </div>
    </div>
    <a href="<?= BASE_URL ?>admin/logout.php" class="sidebar-link" style="margin-top:.25rem;">
      <i class="fas fa-sign-out-alt"></i> Sign Out
    </a>
  </div>
</aside>

<!-- Mobile overlay -->
<div id="sidebar-overlay" class="sidebar-overlay"></div>
