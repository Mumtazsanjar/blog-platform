<?php
/**
 * RSS 2.0 Feed
 */

define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\') . '/');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo   = getDB();
$posts = $pdo->query("
    SELECT p.*, c.name AS category_name, u.display_name AS author_name
    FROM   posts p
    LEFT   JOIN categories c ON c.id = p.category_id
    LEFT   JOIN users u       ON u.id = p.author_id
    WHERE  p.status = 'published'
    ORDER  BY p.created_at DESC
    LIMIT  20
")->fetchAll();

$siteName = getSetting('site_name', 'ModernBlog');
$siteDesc = getSetting('site_description', '');
$siteEmail= getSetting('site_email', '');

header('Content-Type: application/rss+xml; charset=utf-8');
header('X-Content-Type-Options: nosniff');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:dc="http://purl.org/dc/elements/1.1/">
  <channel>
    <title><?= htmlspecialchars($siteName, ENT_XML1) ?></title>
    <link><?= htmlspecialchars(BASE_URL, ENT_XML1) ?></link>
    <description><?= htmlspecialchars($siteDesc, ENT_XML1) ?></description>
    <language>en-us</language>
    <generator>ModernBlog PHP</generator>
    <?php if ($siteEmail): ?>
    <managingEditor><?= htmlspecialchars($siteEmail . ' (' . $siteName . ')', ENT_XML1) ?></managingEditor>
    <?php endif; ?>
    <lastBuildDate><?= date(DATE_RSS) ?></lastBuildDate>
    <atom:link href="<?= htmlspecialchars(BASE_URL . 'feed.php', ENT_XML1) ?>" rel="self" type="application/rss+xml"/>

    <?php foreach ($posts as $post): ?>
    <item>
      <title><?= htmlspecialchars($post['title'], ENT_XML1) ?></title>
      <link><?= htmlspecialchars(BASE_URL . 'post.php?slug=' . $post['slug'], ENT_XML1) ?></link>
      <guid isPermaLink="true"><?= htmlspecialchars(BASE_URL . 'post.php?slug=' . $post['slug'], ENT_XML1) ?></guid>
      <pubDate><?= date(DATE_RSS, strtotime($post['created_at'])) ?></pubDate>
      <dc:creator><?= htmlspecialchars($post['author_name'], ENT_XML1) ?></dc:creator>
      <?php if ($post['category_name']): ?>
      <category><?= htmlspecialchars($post['category_name'], ENT_XML1) ?></category>
      <?php endif; ?>
      <description><?= htmlspecialchars($post['excerpt'] ?: truncate($post['content'], 300), ENT_XML1) ?></description>
      <content:encoded><![CDATA[<?= $post['content'] ?>]]></content:encoded>
    </item>
    <?php endforeach; ?>

  </channel>
</rss>
