<?php
$pageTitle = $pageTitle ?? 'Admin';
?>
<header class="admin-topbar" role="banner">
  <div style="display:flex;align-items:center;gap:1rem;">
    <button id="sidebar-toggle" class="btn-action" aria-label="Toggle sidebar">
      <i class="fas fa-bars" style="font-size:1.1rem;"></i>
    </button>
    <h1 class="topbar-title"><?= e($pageTitle) ?></h1>
  </div>

  <div class="topbar-actions">
    <a href="<?= BASE_URL ?>admin/new-post.php" class="btn btn-primary btn-sm">
      <i class="fas fa-plus"></i> New Post
    </a>
    <a href="<?= BASE_URL ?>index.php" class="btn btn-secondary btn-sm" target="_blank">
      <i class="fas fa-external-link-alt"></i> View Site
    </a>
  </div>
</header>

<style>
.admin-topbar { left: var(--sidebar-w); }
@media(max-width:1024px){
  .admin-topbar { left:0 !important; }
}
</style>
