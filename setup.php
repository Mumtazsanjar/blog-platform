<?php
/**
 * Blog Platform Setup Script
 * Run this once to create all database tables and seed sample data.
 */

$host     = 'localhost';
$dbname   = 'blog_platform';
$username = 'root';
$password = '';
$charset  = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;charset=$charset", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('<p style="color:red">Connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>');
}

$errors   = [];
$messages = [];

try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    $messages[] = "✅ Database '$dbname' created / selected.";
} catch (PDOException $e) {
    $errors[] = "❌ Could not create database: " . $e->getMessage();
}

$tables = [];

$tables['users'] = "CREATE TABLE IF NOT EXISTS `users` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`     VARCHAR(50)  NOT NULL UNIQUE,
  `email`        VARCHAR(120) NOT NULL UNIQUE,
  `password`     VARCHAR(255) NOT NULL,
  `display_name` VARCHAR(100) NOT NULL DEFAULT '',
  `bio`          TEXT,
  `avatar`       VARCHAR(255) DEFAULT NULL,
  `role`         ENUM('admin','editor') NOT NULL DEFAULT 'editor',
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$tables['categories'] = "CREATE TABLE IF NOT EXISTS `categories` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(80)  NOT NULL,
  `slug`        VARCHAR(80)  NOT NULL UNIQUE,
  `description` TEXT,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$tables['tags'] = "CREATE TABLE IF NOT EXISTS `tags` (
  `id`   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(60) NOT NULL UNIQUE,
  `slug` VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$tables['posts'] = "CREATE TABLE IF NOT EXISTS `posts` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`          VARCHAR(255) NOT NULL,
  `slug`           VARCHAR(255) NOT NULL UNIQUE,
  `content`        LONGTEXT NOT NULL,
  `excerpt`        TEXT,
  `category_id`    INT UNSIGNED DEFAULT NULL,
  `author_id`      INT UNSIGNED NOT NULL,
  `featured_image` VARCHAR(255) DEFAULT NULL,
  `status`         ENUM('draft','published') NOT NULL DEFAULT 'draft',
  `featured`       TINYINT(1) NOT NULL DEFAULT 0,
  `views`          INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_posts_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_posts_author`   FOREIGN KEY (`author_id`)   REFERENCES `users`(`id`)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$tables['post_tags'] = "CREATE TABLE IF NOT EXISTS `post_tags` (
  `post_id` INT UNSIGNED NOT NULL,
  `tag_id`  INT UNSIGNED NOT NULL,
  PRIMARY KEY (`post_id`, `tag_id`),
  CONSTRAINT `fk_pt_post` FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pt_tag`  FOREIGN KEY (`tag_id`)  REFERENCES `tags`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$tables['comments'] = "CREATE TABLE IF NOT EXISTS `comments` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `post_id`    INT UNSIGNED NOT NULL,
  `name`       VARCHAR(80)  NOT NULL,
  `email`      VARCHAR(120) NOT NULL,
  `content`    TEXT NOT NULL,
  `ip_address` VARCHAR(45)  DEFAULT NULL,
  `approved`   TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_comments_post` FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$tables['likes'] = "CREATE TABLE IF NOT EXISTS `likes` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `post_id`    INT UNSIGNED NOT NULL,
  `ip_address` VARCHAR(45)  NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_like` (`post_id`, `ip_address`),
  CONSTRAINT `fk_likes_post` FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$tables['settings'] = "CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key`   VARCHAR(80) NOT NULL PRIMARY KEY,
  `setting_value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$tables['contact_messages'] = "CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(80)  NOT NULL,
  `email`       VARCHAR(120) NOT NULL,
  `subject`     VARCHAR(200) NOT NULL,
  `message`     TEXT NOT NULL,
  `read_status` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

foreach ($tables as $name => $sql) {
    try {
        $pdo->exec($sql);
        $messages[] = "✅ Table '$name' ready.";
    } catch (PDOException $e) {
        $errors[] = "❌ Table '$name': " . $e->getMessage();
    }
}

// Add ip_address column to comments if missing (migration for existing installs)
try {
    $cols = $pdo->query("SHOW COLUMNS FROM `comments` LIKE 'ip_address'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE `comments` ADD COLUMN `ip_address` VARCHAR(45) DEFAULT NULL AFTER `content`");
        $messages[] = "✅ Added ip_address column to comments.";
    }
} catch (PDOException $e) {
    $errors[] = "⚠️ Migration ip_address: " . $e->getMessage();
}

// Admin user
$adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO `users` (username, email, password, display_name, bio, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@example.com', $adminPassword, 'Admin User', 'Platform administrator and chief editor.', 'admin']);
    $messages[] = "✅ Admin user seeded (username: admin / password: admin123).";
} catch (PDOException $e) {
    $errors[] = "❌ Admin user: " . $e->getMessage();
}

// Categories
$categories = [
    ['Technology', 'technology', 'Articles about the latest in tech, software, and hardware.'],
    ['Design',     'design',     'UI/UX, graphic design, and creative insights.'],
    ['Tutorials',  'tutorials',  'Step-by-step guides and how-tos.'],
];
foreach ($categories as [$name, $slug, $desc]) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO `categories` (name, slug, description) VALUES (?, ?, ?)");
    $stmt->execute([$name, $slug, $desc]);
}
$messages[] = "✅ Categories seeded.";

// Tags
$tags = [
    ['PHP', 'php'], ['JavaScript', 'javascript'], ['CSS', 'css'],
    ['Web Dev', 'web-dev'], ['Open Source', 'open-source'],
];
foreach ($tags as [$name, $slug]) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO `tags` (name, slug) VALUES (?, ?)");
    $stmt->execute([$name, $slug]);
}
$messages[] = "✅ Tags seeded.";

$authorId  = $pdo->query("SELECT id FROM users WHERE username='admin' LIMIT 1")->fetchColumn();
$catTech   = $pdo->query("SELECT id FROM categories WHERE slug='technology' LIMIT 1")->fetchColumn();
$catDesign = $pdo->query("SELECT id FROM categories WHERE slug='design' LIMIT 1")->fetchColumn();
$catTuts   = $pdo->query("SELECT id FROM categories WHERE slug='tutorials' LIMIT 1")->fetchColumn();

$posts = [
    [
        'title'   => 'Getting Started with Modern PHP Development',
        'slug'    => 'getting-started-modern-php-development',
        'excerpt' => 'A comprehensive guide to setting up a professional PHP development environment with the latest tools and best practices.',
        'content' => '<h2>Introduction</h2><p>PHP has evolved dramatically over the years. With PHP 8.x, the language offers features that rival any modern programming language. In this post, we\'ll walk through setting up a robust PHP development environment.</p><h2>Prerequisites</h2><p>Before we begin, make sure you have the following installed:</p><ul><li>PHP 8.1 or higher</li><li>Composer (dependency manager)</li><li>A modern IDE (VS Code, PhpStorm)</li></ul><h2>Setting Up Your Project</h2><p>Start by creating a new directory and initializing Composer:</p><pre><code class="language-bash">mkdir my-php-project\ncd my-php-project\ncomposer init</code></pre><h2>Using Modern PHP Features</h2><p>PHP 8 introduced many exciting features like named arguments, match expressions, and nullsafe operator.</p><pre><code class="language-php">&lt;?php\n// Named arguments\nfunction createUser(string $name, int $age, string $role = \'user\'): array {\n    return compact(\'name\', \'age\', \'role\');\n}\n$user = createUser(age: 30, name: \'Alice\', role: \'admin\');\n\n// Match expression\n$status = match($user[\'role\']) {\n    \'admin\'  => \'Administrator\',\n    \'editor\' => \'Content Editor\',\n    default  => \'Regular User\',\n};</code></pre><h2>Conclusion</h2><p>Modern PHP is a powerful, expressive language. By adopting these new features and best practices, you can build maintainable, performant applications with confidence.</p>',
        'cat'     => $catTech,
        'image'   => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=800&q=80',
        'featured'=> 1,
        'views'   => 1420,
    ],
    [
        'title'   => 'The Art of UI Design: Creating Beautiful Interfaces',
        'slug'    => 'art-of-ui-design-beautiful-interfaces',
        'excerpt' => 'Explore the principles of modern UI design and how to apply them to create visually stunning, user-friendly interfaces.',
        'content' => '<h2>Design Principles That Matter</h2><p>Great UI design is not just about making things look pretty. It\'s about creating an experience that feels intuitive, efficient, and enjoyable. Let\'s explore the core principles.</p><h2>Visual Hierarchy</h2><p>Visual hierarchy guides the user\'s eye to the most important elements first. You achieve this through size, color, contrast, and spacing.</p><h2>Color Theory</h2><p>Colors evoke emotions and convey meaning. A well-chosen palette can make or break a design. Consider using CSS custom properties for consistent theming:</p><pre><code class="language-css">:root {\n  --color-primary: #6366f1;\n  --color-secondary: #8b5cf6;\n  --color-text: #1e293b;\n  --color-bg: #ffffff;\n}</code></pre><h2>Typography</h2><p>Typography is the foundation of readable, beautiful interfaces. Choose fonts that complement each other and maintain a clear typographic scale.</p><h2>White Space</h2><p>Never underestimate the power of white space. It creates breathing room, improves readability, and gives your design a professional feel.</p><h2>Conclusion</h2><p>Great design comes from understanding your users and applying these principles consistently. Start simple, iterate often, and always test with real users.</p>',
        'cat'     => $catDesign,
        'image'   => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=800&q=80',
        'featured'=> 1,
        'views'   => 980,
    ],
    [
        'title'   => 'Building a RESTful API with PHP and MySQL',
        'slug'    => 'building-restful-api-php-mysql',
        'excerpt' => 'Learn how to build a production-ready RESTful API using PHP, PDO, and MySQL with proper authentication and error handling.',
        'content' => '<h2>What is a RESTful API?</h2><p>REST (Representational State Transfer) is an architectural style for designing networked applications. A RESTful API uses HTTP methods explicitly and follows a stateless communication model.</p><h2>Project Structure</h2><p>A well-organized API project should separate concerns clearly.</p><pre><code class="language-text">api/\n├── index.php\n├── routes/\n│   └── posts.php\n└── controllers/\n    └── PostController.php</code></pre><h2>Creating the Router</h2><pre><code class="language-php">&lt;?php\nheader(\'Content-Type: application/json\');\n$method = $_SERVER[\'REQUEST_METHOD\'];\n$uri    = parse_url($_SERVER[\'REQUEST_URI\'], PHP_URL_PATH);\n\nswitch (true) {\n    case $method === \'GET\'  && $uri === \'/api/posts\':\n        getPosts();\n        break;\n    case $method === \'POST\' && $uri === \'/api/posts\':\n        createPost();\n        break;\n    default:\n        http_response_code(404);\n        echo json_encode([\'error\' => \'Not Found\']);\n}</code></pre><h2>Conclusion</h2><p>A well-structured RESTful API with proper error handling forms the backbone of modern web applications. Follow these patterns and your API will be scalable and maintainable.</p>',
        'cat'     => $catTuts,
        'image'   => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&q=80',
        'featured'=> 0,
        'views'   => 756,
    ],
    [
        'title'   => 'Mastering CSS Grid and Flexbox',
        'slug'    => 'mastering-css-grid-flexbox',
        'excerpt' => 'A deep dive into CSS Grid and Flexbox — the two most powerful layout tools in modern CSS development.',
        'content' => '<h2>Why Layout Matters</h2><p>CSS layout has come a long way from floats and tables. Today, CSS Grid and Flexbox give us powerful, intuitive tools for building complex layouts with clean, minimal code.</p><h2>Flexbox Fundamentals</h2><pre><code class="language-css">.container {\n  display: flex;\n  align-items: center;\n  justify-content: space-between;\n  gap: 1rem;\n}\n.card {\n  flex: 1 1 300px;\n}</code></pre><h2>CSS Grid Fundamentals</h2><pre><code class="language-css">.grid {\n  display: grid;\n  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));\n  gap: 2rem;\n}\n.hero {\n  grid-column: 1 / -1;\n}</code></pre><h2>When to Use Which</h2><p>Use Flexbox when you have items that flow in a single direction. Use Grid when you need precise two-dimensional control over both rows and columns simultaneously.</p>',
        'cat'     => $catTuts,
        'image'   => 'https://images.unsplash.com/photo-1507721999472-8ed4421c4af2?w=800&q=80',
        'featured'=> 0,
        'views'   => 634,
    ],
    [
        'title'   => 'JavaScript Async/Await: A Complete Guide',
        'slug'    => 'javascript-async-await-complete-guide',
        'excerpt' => 'Everything you need to know about asynchronous JavaScript — from callbacks and Promises to async/await patterns.',
        'content' => '<h2>The Evolution of Async JS</h2><p>JavaScript is single-threaded, but it handles asynchronous operations through callbacks, Promises, and now async/await. Understanding this evolution helps you write better code.</p><h2>Callbacks (The Old Way)</h2><pre><code class="language-javascript">fetchData(url, function(error, data) {\n  if (error) { console.error(error); return; }\n  processData(data, function(error, result) {\n    // Callback hell begins...\n  });\n});</code></pre><h2>Async/Await (Modern Approach)</h2><pre><code class="language-javascript">async function loadUserData(userId) {\n  try {\n    const user  = await fetchUser(userId);\n    const posts = await fetchPosts(user.id);\n    return { user, posts };\n  } catch (error) {\n    console.error(\'Failed:\', error);\n    throw error;\n  }\n}\n\n// Parallel execution\nasync function loadAll(userId) {\n  const [user, settings] = await Promise.all([\n    fetchUser(userId),\n    fetchSettings(userId)\n  ]);\n  return { user, settings };\n}</code></pre><h2>Conclusion</h2><p>Async/await makes asynchronous code readable and maintainable. Embrace it, but understand Promises underneath to debug effectively.</p>',
        'cat'     => $catTech,
        'image'   => 'https://images.unsplash.com/photo-1579468118864-1b9ea3c0db4a?w=800&q=80',
        'featured'=> 1,
        'views'   => 1105,
    ],
];

$insertPost = $pdo->prepare("INSERT IGNORE INTO `posts` (title, slug, content, excerpt, category_id, author_id, featured_image, status, featured, views) VALUES (?, ?, ?, ?, ?, ?, ?, 'published', ?, ?)");
foreach ($posts as $p) {
    try {
        $insertPost->execute([$p['title'], $p['slug'], $p['content'], $p['excerpt'], $p['cat'], $authorId, $p['image'], $p['featured'], $p['views']]);
    } catch (PDOException $e) {
        $errors[] = "❌ Post '{$p['title']}': " . $e->getMessage();
    }
}
$messages[] = "✅ Sample posts seeded.";

// Comments
$postIds = $pdo->query("SELECT id FROM posts LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
$sampleComments = [
    ['Alice Johnson', 'alice@example.com', 'This is a fantastic article! Really helped me understand the concepts. Looking forward to more posts like this.'],
    ['Bob Smith',     'bob@example.com',   'Great post, very well written. Would love to see a follow-up on advanced topics.'],
];
$insertComment = $pdo->prepare("INSERT INTO `comments` (post_id, name, email, content, approved) VALUES (?, ?, ?, ?, 1)");
foreach ($postIds as $pid) {
    foreach ($sampleComments as $c) {
        try { $insertComment->execute([$pid, $c[0], $c[1], $c[2]]); } catch (PDOException $e) { /* skip */ }
    }
}
$messages[] = "✅ Sample comments seeded.";

// Settings
$settings = [
    ['site_name',        'ModernBlog'],
    ['site_description', 'A modern, professional blog platform built with PHP.'],
    ['site_email',       'contact@modernblog.com'],
    ['posts_per_page',   '6'],
    ['allow_comments',   '1'],
    ['social_twitter',   'https://twitter.com/modernblog'],
    ['social_github',    'https://github.com/modernblog'],
    ['footer_text',      '© 2024 ModernBlog. All rights reserved.'],
];
$insertSetting = $pdo->prepare("INSERT IGNORE INTO `settings` (setting_key, setting_value) VALUES (?, ?)");
foreach ($settings as [$k, $v]) { $insertSetting->execute([$k, $v]); }
$messages[] = "✅ Default settings seeded.";

// Tags
$phpTag = $pdo->query("SELECT id FROM tags WHERE slug='php' LIMIT 1")->fetchColumn();
$jsTag  = $pdo->query("SELECT id FROM tags WHERE slug='javascript' LIMIT 1")->fetchColumn();
$cssTag = $pdo->query("SELECT id FROM tags WHERE slug='css' LIMIT 1")->fetchColumn();
$slugTagMap = [
    'getting-started-modern-php-development' => [$phpTag],
    'building-restful-api-php-mysql'         => [$phpTag],
    'mastering-css-grid-flexbox'             => [$cssTag],
    'javascript-async-await-complete-guide'  => [$jsTag],
];
$insertPT = $pdo->prepare("INSERT IGNORE INTO `post_tags` (post_id, tag_id) VALUES (?, ?)");
foreach ($slugTagMap as $slug => $tids) {
    $ps = $pdo->prepare("SELECT id FROM posts WHERE slug=? LIMIT 1");
    $ps->execute([$slug]);
    $postId = $ps->fetchColumn();
    if ($postId) { foreach ($tids as $tid) { $insertPT->execute([$postId, $tid]); } }
}
$messages[] = "✅ Post tags seeded.";

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blog Platform Setup</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#0f172a;color:#e2e8f0;display:flex;justify-content:center;padding:2rem;min-height:100vh}
  .card{background:#1e293b;border-radius:12px;padding:2rem;max-width:640px;width:100%;box-shadow:0 25px 50px rgba(0,0,0,.4);margin:auto}
  h1{font-size:1.8rem;margin-bottom:.25rem;color:#6366f1}
  .sub{color:#94a3b8;margin-bottom:1.5rem}
  .log{list-style:none;font-size:.9rem;line-height:2}
  .log li{border-bottom:1px solid #334155;padding:.2rem 0}
  .errors{background:#450a0a;border:1px solid #b91c1c;border-radius:8px;padding:1rem;margin-top:1rem}
  .errors li{color:#fca5a5}
  .actions{margin-top:1.5rem;display:flex;gap:.75rem;flex-wrap:wrap}
  .btn{display:inline-block;padding:.6rem 1.4rem;border-radius:8px;text-decoration:none;font-weight:600;font-size:.9rem}
  .btn-primary{background:#6366f1;color:#fff}
  .btn-secondary{background:#334155;color:#e2e8f0}
  .success-banner{background:#052e16;border:1px solid #166534;color:#86efac;border-radius:8px;padding:1rem 1.25rem;margin-bottom:1rem;font-weight:600}
</style>
</head>
<body>
<div class="card">
  <h1>🚀 Blog Platform Setup</h1>
  <p class="sub">Database initialization and sample data seeding</p>
  <?php if (empty($errors)): ?><div class="success-banner">✅ Setup completed successfully!</div><?php endif; ?>
  <ul class="log">
    <?php foreach ($messages as $m): ?><li><?= htmlspecialchars($m) ?></li><?php endforeach; ?>
  </ul>
  <?php if (!empty($errors)): ?>
  <ul class="errors log">
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul>
  <?php endif; ?>
  <div class="actions">
    <a href="index.php" class="btn btn-primary">Go to Blog →</a>
    <a href="login.php" class="btn btn-secondary">Admin Login</a>
  </div>
  <p style="margin-top:1rem;color:#64748b;font-size:.8rem;">⚠️ Delete or restrict access to setup.php after setup.</p>
</div>
</body>
</html>
