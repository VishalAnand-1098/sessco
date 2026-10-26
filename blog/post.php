<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/blog.php';
require __DIR__ . '/../inc/layout.php';

$slug = (string)($_GET['slug'] ?? '');
$p = $slug !== '' ? get_post($slug) : null;
$preview = false;
if ($p && ($p['status'] ?? '') !== 'published') {
  // drafts are visible only to a logged-in admin (preview)
  if (session_status() !== PHP_SESSION_ACTIVE) { session_name('jassco_admin'); session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']); }
  $preview = !empty($_SESSION['admin']);
  if (!$preview) $p = null;
}
if (!$p) { http_response_code(404); readfile(PUB . '/404.html'); exit; }

$path = '/blog/' . $p['slug'] . '/';
$trail = [['Home', '/'], ['Blog', '/blog/'], [$p['title'], null]];
$date = date('d F Y', strtotime($p['date']));
$cover = $p['cover'] ? '<figure style="margin:0 0 28px;border-radius:8px;overflow:hidden;border:1px solid var(--line)"><img src="' . e($p['cover']) . '" alt="' . e($p['cover_alt'] ?: $p['title']) . '" width="1200" height="800" style="width:100%;height:auto"></figure>' : '';
$others = array_values(array_filter(all_posts(true), fn($x) => $x['slug'] !== $p['slug']));
$more = '';
foreach (array_slice($others, 0, 4) as $o) $more .= '<li><a href="/blog/' . e($o['slug']) . '/">' . e($o['title']) . '</a></li>';
$ld = ['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $p['title'], 'description' => $p['meta_description'] ?: $p['excerpt'],
  'datePublished' => date('c', strtotime($p['date'])), 'dateModified' => date('c', strtotime($p['updated'] ?? $p['date'])),
  'mainEntityOfPage' => site_url() . $path, 'author' => ['@type' => 'Organization', 'name' => $p['author'] ?: 'JASSCO Steel'],
  'publisher' => ['@type' => 'Organization', 'name' => 'JASSCO Steel Private Limited', 'logo' => ['@type' => 'ImageObject', 'url' => site_url() . '/assets/img/icon-512.png']]];
if ($p['cover']) $ld['image'] = (str_starts_with($p['cover'], 'http') ? '' : site_url()) . $p['cover'];

$banner = $preview ? '<div style="background:#FFF8E6;border:1px solid #F5DFA0;padding:10px 14px;border-radius:4px;margin-bottom:20px">Draft preview — only visible to admins. <a href="/admin/?p=edit&slug=' . e($p['slug']) . '">Back to editor</a></div>' : '';
$body = page_hero($trail, 'Blog · ' . $date . ' · ' . reading_time($p['content']) . ' min read', $p['title'], e($p['excerpt']), false)
 . '<section class="sec tight"><div class="wrap content"><article class="prose blog-body">' . $banner . $cover . $p['content'] . '</article>'
 . '<aside class="aside">' . aside_cta() . ($more ? '<div class="box"><h3>More articles</h3><ul>' . $more . '</ul></div>' : '') . '<div class="box"><h3>Explore</h3><ul><li><a href="/products/">Products</a></li><li><a href="/capabilities/">Manufacturing capabilities</a></li><li><a href="/machinery/">Machinery directory</a></li></ul></div></aside>'
 . '</div></section>';
render_page($path, ($p['meta_title'] ?: $p['title'] . ' | JASSCO Steel'), ($p['meta_description'] ?: $p['excerpt']), $body, 'blog', $trail, [$ld], $preview, $p['cover'] ?: '');
