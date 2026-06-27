<?php
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\') . '/');

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
$admin = getCurrentAdmin();
$pdo   = getDB();

$errors = [];

// Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $name   = trim($_POST['name'] ?? '');
    $slug   = trim($_POST['slug'] ?? '');
    $desc   = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $errors[] = 'Category name is required.';
    } else {
        $slug = $slug ?: createSlug($name);
        $editCatId = (int)($_POST['edit_id'] ?? 0);
        $slug = uniqueSlug($slug, 'categories', $editCatId);

        if ($action === 'edit' && $editCatId) {
            $pdo->prepare("UPDATE categories SET name=?,slug=?,description=? WHERE id=?")->execute([$name, $slug, $desc, $editCatId]);
            setFlash('success', 'Category updated.');
        } else {
            $pdo->prepare("INSERT INTO categories (name,slug,description) VALUES (?,?,?)")->execute([$name, $slug, $desc]);
            setFlash('success', 'Category created.');
        }
        header('Location: ' . BASE_URL . 'admin/categories.php');
        exit;
    }
}

// Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && verifyCsrfToken($_GET['token'] ?? '')) {
    $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlash('success', 'Category deleted.');
    header('Location: ' . BASE_URL . 'admin/categories.php');
    exit;
}

// Load for edit
$editCat = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCat = $stmt->fetch();
}

$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS post_count
    FROM categories c
    LEFT JOIN posts p ON p.category_id=c.id
    GROUP BY c.id ORDER BY c.name
")->fetchAll();

$pendingComments = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE approved=0")->fetchColumn();
$pageTitle = 'Categories';
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
      <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>

      <div style="display:grid;grid-template-columns:1fr 380px;gap:1.5rem;align-items:start;" class="cat-grid">

        <!-- Category List -->
        <div class="admin-card">
          <div class="admin-card-header">
            <h3 class="admin-card-title">All Categories</h3>
          </div>
          <div class="table-responsive">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Slug</th>
                  <th>Posts</th>
                  <th>Description</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($categories)): ?>
                <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-muted);">No categories yet.</td></tr>
                <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                <tr>
                  <td style="font-weight:600;"><?= e($cat['name']) ?></td>
                  <td><code style="font-size:.8rem;color:var(--primary);"><?= e($cat['slug']) ?></code></td>
                  <td>
                    <a href="<?= BASE_URL ?>admin/posts.php?category=<?= $cat['id'] ?>" style="color:var(--text-muted);">
                      <?= $cat['post_count'] ?>
                    </a>
                  </td>
                  <td style="color:var(--text-muted);font-size:.85rem;max-width:200px;">
                    <?= e(truncate($cat['description'] ?? '', 60)) ?>
                  </td>
                  <td>
                    <div class="action-btns">
                      <a href="?edit=<?= $cat['id'] ?>" class="btn-action edit" title="Edit"><i class="fas fa-edit"></i></a>
                      <a href="<?= BASE_URL ?>category.php?slug=<?= e($cat['slug']) ?>" class="btn-action view" title="View" target="_blank"><i class="fas fa-eye"></i></a>
                      <a href="?delete=<?= $cat['id'] ?>&token=<?= e(generateCsrfToken()) ?>"
                         class="btn-action delete" title="Delete"
                         data-confirm="Delete category &quot;<?= e(addslashes($cat['name'])) ?>&quot;? Posts will not be deleted, but will lose their category.">
                        <i class="fas fa-trash"></i>
                      </a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Add / Edit Form -->
        <div class="admin-card">
          <div class="admin-card-header">
            <h3 class="admin-card-title">
              <?= $editCat ? '<i class="fas fa-edit"></i> Edit Category' : '<i class="fas fa-plus"></i> Add New Category' ?>
            </h3>
            <?php if ($editCat): ?>
            <a href="<?= BASE_URL ?>admin/categories.php" class="btn btn-secondary btn-sm">Cancel</a>
            <?php endif; ?>
          </div>
          <div class="admin-card-body">
            <form method="post">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="<?= $editCat ? 'edit' : 'add' ?>">
              <?php if ($editCat): ?>
              <input type="hidden" name="edit_id" value="<?= $editCat['id'] ?>">
              <?php endif; ?>

              <div class="form-group">
                <label class="form-label" for="cat-name">Name *</label>
                <input type="text" id="cat-name" name="name" class="form-control"
                       value="<?= e($editCat['name'] ?? '') ?>" required maxlength="80">
              </div>
              <div class="form-group">
                <label class="form-label" for="cat-slug">Slug</label>
                <input type="text" id="cat-slug" name="slug" class="form-control"
                       value="<?= e($editCat['slug'] ?? '') ?>" maxlength="80"
                       placeholder="auto-generated from name">
              </div>
              <div class="form-group">
                <label class="form-label" for="cat-desc">Description</label>
                <textarea id="cat-desc" name="description" class="form-control" rows="3"><?= e($editCat['description'] ?? '') ?></textarea>
              </div>
              <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i class="fas fa-save"></i> <?= $editCat ? 'Update Category' : 'Add Category' ?>
              </button>
            </form>
          </div>
        </div>

      </div>

    </main>
  </div>
</div>

<style>
@media(max-width:1024px) { .cat-grid { grid-template-columns:1fr !important; } }
</style>

<script>
// Auto-generate slug from name
document.getElementById('cat-name')?.addEventListener('input', function() {
  const slugEl = document.getElementById('cat-slug');
  if (!slugEl.value || slugEl.dataset.auto !== 'false') {
    slugEl.value = this.value.toLowerCase().replace(/[^a-z0-9\s-]/g,'').replace(/[\s]+/g,'-').replace(/-+/g,'-').trim();
    slugEl.dataset.auto = 'true';
  }
});
document.getElementById('cat-slug')?.addEventListener('input', function() {
  this.dataset.auto = 'false';
});
</script>
<script src="<?= BASE_URL ?>assets/js/admin.js" defer></script>
</body>
</html>
