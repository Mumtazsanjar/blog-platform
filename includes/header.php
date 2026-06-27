<?php
/**
 * Public site header
 * Expects $pageTitle, $pageDescription, $ogImage (optional) to be set before include.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script   = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    // Determine base URL relative to blog-platform folder
    $path = rtrim(str_replace('\\', '/', $script), '/');
    // Remove admin/ or api/ suffix if nested
    $path = preg_replace('/(\/admin|\/api)$/', '', $path);
    define('BASE_URL', $protocol . '://' . $host . $path . '/');
}

require_once __DIR__ . '/functions.php';

$siteName = getSetting('site_name', 'ModernBlog');
$siteDesc = getSetting('site_description', 'A modern blog platform.');
$pageTitle       = $pageTitle       ?? $siteName;
$pageDescription = $pageDescription ?? $siteDesc;
$ogImage         = $ogImage         ?? '';
$categories      = getCategories();
$currentPage     = basename($_SERVER['PHP_SELF'], '.php');

$fullTitle = ($pageTitle !== $siteName) ? "$pageTitle — $siteName" : $siteName;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($fullTitle) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>">

  <!-- Open Graph -->
  <meta property="og:title"       content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($pageDescription) ?>">
  <meta property="og:type"        content="website">
  <meta property="og:url"         content="<?= e((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '')) ?>">
  <?php if ($ogImage): ?>
  <meta property="og:image" content="<?= e($ogImage) ?>">
  <?php endif; ?>

  <!-- Twitter Card -->
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="<?= e($pageTitle) ?>">
  <meta name="twitter:description" content="<?= e($pageDescription) ?>">
  <?php if ($ogImage): ?>
  <meta name="twitter:image" content="<?= e($ogImage) ?>">
  <?php endif; ?>

  <!-- Canonical -->
  <link rel="canonical" href="<?= e(BASE_URL . ltrim($_SERVER['REQUEST_URI'] ?? '', '/')) ?>">
  <!-- Base URL for JS -->
  <meta name="base-url" content="<?= e(BASE_URL) ?>">
  <!-- RSS -->
  <link rel="alternate" type="application/rss+xml" title="<?= e($siteName) ?> RSS Feed" href="<?= BASE_URL ?>feed.php">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

  <!-- Prism.js syntax highlighting -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" crossorigin="anonymous">

  <!-- Main CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<!-- Reading Progress Bar -->
<div id="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>

<!-- Navigation -->
<nav class="navbar" id="navbar" aria-label="Main navigation">
  <div class="nav-container">

    <a href="<?= BASE_URL ?>index.php" class="nav-logo" aria-label="<?= e($siteName) ?> home">
      <i class="fas fa-feather-alt"></i> <?= e($siteName) ?>
    </a>

    <ul class="nav-links" role="list">
      <li><a href="<?= BASE_URL ?>index.php"    class="<?= $currentPage === 'index'    ? 'active' : '' ?>">Home</a></li>
      <?php foreach (array_slice($categories, 0, 4) as $cat): ?>
      <li><a href="<?= BASE_URL ?>category.php?slug=<?= e($cat['slug']) ?>"
             class="<?= (isset($_GET['slug']) && $_GET['slug'] === $cat['slug']) ? 'active' : '' ?>">
        <?= e($cat['name']) ?>
      </a></li>
      <?php endforeach; ?>
      <li><a href="<?= BASE_URL ?>about.php"    class="<?= $currentPage === 'about'    ? 'active' : '' ?>">About</a></li>
      <li><a href="<?= BASE_URL ?>contact.php"  class="<?= $currentPage === 'contact'  ? 'active' : '' ?>">Contact</a></li>
    </ul>

    <div class="nav-actions">
      <!-- Live search -->
      <div class="nav-search" role="search">
        <i class="fas fa-search search-icon" aria-hidden="true"></i>
        <input type="search"
               class="live-search-input"
               placeholder="Search…"
               aria-label="Search posts"
               autocomplete="off">
        <div class="search-dropdown" id="search-dropdown" role="listbox" aria-label="Search results"></div>
      </div>

      <!-- Theme toggle -->
      <button id="theme-toggle" class="btn-icon" aria-label="Toggle dark mode" title="Toggle dark/light mode">
        <i class="fas fa-moon"></i>
      </button>

      <!-- RSS -->
      <a href="<?= BASE_URL ?>feed.php" class="btn-icon" aria-label="RSS Feed" title="RSS Feed">
        <i class="fas fa-rss"></i>
      </a>
    </div>

    <!-- Hamburger -->
    <button class="hamburger" id="hamburger" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobile-menu">
      <span></span><span></span><span></span>
    </button>

  </div>
</nav>

<!-- Mobile Menu -->
<div id="mobile-menu" class="mobile-menu" role="navigation" aria-label="Mobile navigation">
  <a href="<?= BASE_URL ?>index.php">Home</a>
  <?php foreach ($categories as $cat): ?>
  <a href="<?= BASE_URL ?>category.php?slug=<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></a>
  <?php endforeach; ?>
  <a href="<?= BASE_URL ?>about.php">About</a>
  <a href="<?= BASE_URL ?>contact.php">Contact</a>
  <div class="mobile-search">
    <input type="search" class="live-search-input" placeholder="Search posts…" aria-label="Search posts">
  </div>
</div>

<main id="main-content">
