<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/blog.php';
require __DIR__ . '/../inc/layout.php';

$posts = all_posts(true);
$per = 12; $page = max(1, (int)($_GET['page'] ?? 1)); $pages = max(1, (int)ceil(count($posts) / $per));
$page = min($page, $pages); $list = array_slice($posts, ($page - 1) * $per, $per);
$path = '/blog/' . ($page > 1 ? '?page=' . $page : '');

$cards = '';
foreach ($list as $p) {
  $img = $p['cover'] ? '<div class="ci"><img src="' . e($p['cover']) . '" alt="' . e($p['cover_alt'] ?: $p['title']) . '" loading="lazy" width="800" height="600"></div>' : '';
  $cards .= '<a class="card" href="/blog/' . e($p['slug']) . '/">' . $img . '<div class="cb"><span class="tag">' . e(date('d M Y', strtotime($p['date']))) . ' · ' . reading_time($p['content']) . ' min read</span><h3>' . e($p['title']) . '</h3><p>' . e($p['excerpt']) . '</p><span class="more">Read article ' . ICON_ARROW . '</span></div></a>';
}
if (!$cards) $cards = '<p class="muted">New articles are coming soon.</p>';
$nav = '';
if ($pages > 1) { $nav = '<div class="btn-row" style="margin-top:32px">'; if ($page > 1) $nav .= '<a class="btn btn-outline btn-sm" href="/blog/' . ($page - 1 > 1 ? '?page=' . ($page - 1) : '') . '">← Newer</a>'; if ($page < $pages) $nav .= '<a class="btn btn-outline btn-sm" href="/blog/?page=' . ($page + 1) . '">Older →</a>'; $nav .= '</div>'; }

$trail = [['Home', '/'], ['Blog', null]];
$body = page_hero($trail, 'Insights', 'Metal manufacturing insights', 'Practical guidance for engineers, buyers and project teams on perforated metal, expanded mesh, sheet metal fabrication and sourcing from India.')
  . '<section class="sec tight"><div class="wrap"><div class="g g-3">' . $cards . '</div>' . $nav . '</div></section>';
render_page('/blog/' . ($page > 1 ? '?page=' . $page : ''), 'Blog – Sheet Metal, Perforated Metal & Fabrication Insights | JASSCO',
  'Guides on perforated and expanded metal, cable trays, laser cutting, bending and sourcing custom metal components from India, from JASSCO Steel.', $body, 'blog', $trail);
