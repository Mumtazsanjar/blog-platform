<?php
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\') . '/');

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
$admin = getCurrentAdmin();
$pdo   = getDB();

$editId = (int)($_GET['id'] ?? 0);
$post   = null;
$postTags = [];

if ($editId) {
    $post = getPostById($editId);
    if (!$post) { setFlash('error', 'Post not found.'); header('Location: ' . BASE_URL . 'admin/posts.php'); exit; }
    $postTags = array_column(getPostTags($editId), 'name');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request.';
    } else {
        $title    = trim($_POST['title']          ?? '');
        $slug     = trim($_POST['slug']           ?? '');
        $content  = $_POST['content']             ?? '';
        $excerpt  = trim($_POST['excerpt']        ?? '');
        $catId    = (int)($_POST['category_id']   ?? 0) ?: null;
        $status   = $_POST['status']              === 'published' ? 'published' : 'draft';
        $featured = !empty($_POST['featured'])    ? 1 : 0;
        $featImg  = trim($_POST['featured_image'] ?? '');
        $tagsRaw  = trim($_POST['tags_hidden']    ?? '');

        if (empty($title))   $errors[] = 'Title is required.';
        if (empty($content)) $errors[] = 'Content is required.';

        if (empty($errors)) {
            $slug = $slug ?: createSlug($title);
            $slug = uniqueSlug($slug, 'posts', $editId);

            if ($editId) {
                $stmt = $pdo->prepare("UPDATE posts SET title=?,slug=?,content=?,excerpt=?,category_id=?,featured_image=?,status=?,featured=?,updated_at=NOW() WHERE id=?");
                $stmt->execute([$title, $slug, $content, $excerpt, $catId, $featImg, $status, $featured, $editId]);
                $postId = $editId;
                setFlash('success', 'Post updated successfully.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO posts (title,slug,content,excerpt,category_id,author_id,featured_image,status,featured) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$title, $slug, $content, $excerpt, $catId, $admin['id'], $featImg, $status, $featured]);
                $postId = (int)$pdo->lastInsertId();
                setFlash('success', 'Post created successfully.');
            }

            // Sync tags
            $pdo->prepare("DELETE FROM post_tags WHERE post_id=?")->execute([$postId]);
            if ($tagsRaw) {
                $tagNames = array_filter(array_map('trim', explode(',', $tagsRaw)));
                foreach ($tagNames as $tagName) {
                    $tagName = mb_strtolower($tagName);
                    $tagSlug = createSlug($tagName);
                    $stmt = $pdo->prepare("INSERT IGNORE INTO tags (name, slug) VALUES (?, ?)");
                    $stmt->execute([$tagName, $tagSlug]);
                    $tagId = $pdo->prepare("SELECT id FROM tags WHERE slug=? LIMIT 1");
                    $tagId->execute([$tagSlug]);
                    $tid = $tagId->fetchColumn();
                    if ($tid) $pdo->prepare("INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?,?)")->execute([$postId, $tid]);
                }
            }

            header('Location: ' . BASE_URL . 'admin/posts.php');
            exit;
        }
    }
}

$categories      = getCategories();
$pendingComments = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE approved=0")->fetchColumn();
$pageTitle       = $editId ? 'Edit Post' : 'New Post';
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
  <!-- Quill Rich Text Editor -->
  <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
  <!-- Prism for code highlighting preview -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" crossorigin="anonymous">
</head>
<body>
<div class="admin-wrapper">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div style="flex:1;display:flex;flex-direction:column;min-width:0;">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <main class="admin-main">

      <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="post" id="post-form">
        <?= csrfField() ?>

        <div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;">

          <!-- Left column -->
          <div>
            <div class="admin-card" style="margin-bottom:1.25rem;">
              <div class="admin-card-body">
                <div class="form-group">
                  <label class="form-label" for="post-title">Post Title *</label>
                  <input type="text"
                         id="post-title"
                         name="title"
                         class="form-control"
                         style="font-size:1.25rem;font-weight:700;"
                         value="<?= e($_POST['title'] ?? $post['title'] ?? '') ?>"
                         placeholder="Enter post title…"
                         required>
                </div>
                <div class="form-group">
                  <label class="form-label" for="post-slug">Slug</label>
                  <input type="text"
                         id="post-slug"
                         name="slug"
                         class="form-control"
                         value="<?= e($_POST['slug'] ?? $post['slug'] ?? '') ?>"
                         placeholder="post-url-slug">
                  <div class="slug-preview">
                    URL: <?= BASE_URL ?>post.php?slug=<span id="slug-preview-value"><?= e($_POST['slug'] ?? $post['slug'] ?? 'your-post-slug') ?></span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Content editor -->
            <div class="admin-card" style="margin-bottom:1.25rem;">
              <div class="admin-card-header">
                <h3 class="admin-card-title"><i class="fas fa-pen"></i> Content</h3>
                <div style="display:flex;gap:.5rem;">
                  <button type="button" id="btn-visual" class="btn btn-primary btn-sm" onclick="switchEditor('visual')">Visual</button>
                  <button type="button" id="btn-html" class="btn btn-secondary btn-sm" onclick="switchEditor('html')">HTML</button>
                </div>
              </div>
              <div class="admin-card-body" style="padding:0;">
                <!-- Quill Visual Editor -->
                <div id="quill-editor" style="min-height:400px;font-size:1rem;"></div>
                <!-- Raw HTML Editor -->
                <textarea id="content-html" name="content" class="form-control"
                          style="display:none;min-height:400px;border-radius:0;border:none;font-family:'Fira Code',monospace;font-size:.875rem;resize:vertical;"><?= htmlspecialchars($_POST['content'] ?? $post['content'] ?? '', ENT_QUOTES) ?></textarea>
              </div>
            </div>

            <!-- Excerpt -->
            <div class="admin-card">
              <div class="admin-card-header">
                <h3 class="admin-card-title"><i class="fas fa-align-left"></i> Excerpt</h3>
              </div>
              <div class="admin-card-body">
                <textarea name="excerpt" class="form-control" rows="3" placeholder="Short description (used in post cards and SEO)…"><?= e($_POST['excerpt'] ?? $post['excerpt'] ?? '') ?></textarea>
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div style="display:flex;flex-direction:column;gap:1.25rem;">

            <!-- Publish -->
            <div class="admin-card">
              <div class="admin-card-header">
                <h3 class="admin-card-title"><i class="fas fa-upload"></i> Publish</h3>
              </div>
              <div class="admin-card-body">
                <div class="form-group">
                  <label class="form-label" for="status">Status</label>
                  <select name="status" id="status" class="form-control">
                    <?php $curStatus = $_POST['status'] ?? $post['status'] ?? 'draft'; ?>
                    <option value="draft"     <?= $curStatus==='draft'     ?'selected':'' ?>>Draft</option>
                    <option value="published" <?= $curStatus==='published' ?'selected':'' ?>>Published</option>
                  </select>
                </div>
                <div class="toggle-group" style="margin-bottom:1rem;">
                  <label class="toggle">
                    <input type="checkbox" name="featured" <?= (!empty($_POST['featured']) || (!empty($post['featured']))) ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                  </label>
                  <span class="toggle-label">Featured Post</span>
                </div>
                <div style="display:flex;gap:.75rem;">
                  <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;">
                    <i class="fas fa-save"></i> <?= $editId ? 'Update' : 'Publish' ?>
                  </button>
                  <a href="<?= BASE_URL ?>admin/posts.php" class="btn btn-secondary btn-sm">Cancel</a>
                </div>
                <?php if ($editId && $post['status'] === 'published'): ?>
                <a href="<?= BASE_URL ?>post.php?slug=<?= e($post['slug']) ?>" target="_blank"
                   style="display:block;text-align:center;margin-top:.75rem;font-size:.8rem;color:var(--text-muted);">
                  <i class="fas fa-external-link-alt"></i> View Post
                </a>
                <?php endif; ?>
              </div>
            </div>

            <!-- Category -->
            <div class="admin-card">
              <div class="admin-card-header">
                <h3 class="admin-card-title"><i class="fas fa-folder"></i> Category</h3>
              </div>
              <div class="admin-card-body">
                <select name="category_id" class="form-control">
                  <option value="0">— No Category —</option>
                  <?php $selCat = (int)($_POST['category_id'] ?? $post['category_id'] ?? 0); ?>
                  <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>" <?= $selCat==$cat['id']?'selected':''?>>
                    <?= e($cat['name']) ?>
                  </option>
                  <?php endforeach; ?>
                </select>
                <a href="<?= BASE_URL ?>admin/categories.php" style="font-size:.75rem;color:var(--text-muted);display:block;margin-top:.5rem;">
                  + Manage categories
                </a>
              </div>
            </div>

            <!-- Tags -->
            <div class="admin-card">
              <div class="admin-card-header">
                <h3 class="admin-card-title"><i class="fas fa-tags"></i> Tags</h3>
              </div>
              <div class="admin-card-body">
                <div class="tags-input-wrapper" data-target="tags_hidden">
                  <input type="text" placeholder="Add tag, press Enter…" aria-label="Add tag">
                </div>
                <input type="hidden" id="tags_hidden" name="tags_hidden" value="<?= e(implode(',', array_column((!empty($post) ? getPostTags($post['id']) : []), 'name'))) ?>">
                <p class="form-hint">Press Enter or comma to add each tag.</p>
              </div>
            </div>

            <!-- Featured Image -->
            <div class="admin-card">
              <div class="admin-card-header">
                <h3 class="admin-card-title"><i class="fas fa-image"></i> Featured Image</h3>
              </div>
              <div class="admin-card-body">
                <div class="form-group">
                  <label class="form-label" for="featured-image-url">Image URL</label>
                  <input type="url"
                         id="featured-image-url"
                         name="featured_image"
                         class="form-control"
                         value="<?= e($_POST['featured_image'] ?? $post['featured_image'] ?? '') ?>"
                         placeholder="https://example.com/image.jpg">
                </div>
                <img id="featured-image-preview"
                     src="<?= e($_POST['featured_image'] ?? $post['featured_image'] ?? '') ?>"
                     alt="Preview"
                     style="display:<?= (!empty($_POST['featured_image']) || !empty($post['featured_image'])) ? 'block' : 'none' ?>;width:100%;border-radius:var(--radius-sm);max-height:180px;object-fit:cover;margin-top:.5rem;">
              </div>
            </div>

          </div>
        </div>
      </form>

    </main>
  </div>
</div>

<style>
@media(max-width:1024px){
  form > div { grid-template-columns: 1fr !important; }
}
/* Quill dark theme overrides */
#quill-editor {
  background: var(--bg);
  color: var(--text);
  border: none;
}
.ql-toolbar.ql-snow {
  border: none;
  border-bottom: 1px solid var(--border);
  background: var(--bg-secondary);
  padding: .75rem 1.5rem;
}
.ql-container.ql-snow { border: none; }
.ql-snow .ql-stroke { stroke: var(--text-muted); }
.ql-snow .ql-fill  { fill: var(--text-muted); }
.ql-snow .ql-picker-label { color: var(--text-muted); }
.ql-snow .ql-picker-options { background: var(--bg-secondary); border-color: var(--border); }
.ql-editor { min-height: 400px; font-size: 1rem; line-height: 1.7; padding: 1.5rem; }
.ql-editor.ql-blank::before { color: var(--text-light); }
</style>

<script>
// Init Quill
const initialContent = document.getElementById('content-html').value;
const quill = new Quill('#quill-editor', {
  theme: 'snow',
  placeholder: 'Write your post content here…',
  modules: {
    toolbar: [
      [{ header: [1,2,3,false] }],
      ['bold','italic','underline','strike'],
      ['blockquote','code-block'],
      [{ list: 'ordered' },{ list: 'bullet' }],
      [{ indent: '-1' },{ indent: '+1' }],
      ['link','image'],
      ['clean'],
    ],
  },
});

// Load existing content
if (initialContent) {
  quill.clipboard.dangerouslyPasteHTML(initialContent);
}

let currentMode = 'visual';

function switchEditor(mode) {
  const quillWrap = document.getElementById('quill-editor');
  const htmlTA    = document.getElementById('content-html');
  const btnVisual = document.getElementById('btn-visual');
  const btnHtml   = document.getElementById('btn-html');

  if (mode === 'html') {
    // Sync quill → textarea
    htmlTA.value = quill.root.innerHTML;
    quillWrap.style.display = 'none';
    htmlTA.style.display    = 'block';
    btnVisual.className = 'btn btn-secondary btn-sm';
    btnHtml.className   = 'btn btn-primary btn-sm';
  } else {
    // Sync textarea → quill
    quill.clipboard.dangerouslyPasteHTML(htmlTA.value);
    quillWrap.style.display = 'block';
    htmlTA.style.display    = 'none';
    btnVisual.className = 'btn btn-primary btn-sm';
    btnHtml.className   = 'btn btn-secondary btn-sm';
  }
  currentMode = mode;
}

// Before form submit, sync content
document.getElementById('post-form').addEventListener('submit', function() {
  if (currentMode === 'visual') {
    document.getElementById('content-html').value = quill.root.innerHTML;
    document.getElementById('content-html').style.display = 'block';
  }
});
</script>
</body>
</html>
