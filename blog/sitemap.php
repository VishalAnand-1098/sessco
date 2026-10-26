<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/blog.php';
header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
echo '  <url><loc>' . e(site_url()) . '/blog/</loc><lastmod>' . date('Y-m-d') . "</lastmod></url>\n";
foreach (all_posts(true) as $p) echo '  <url><loc>' . e(site_url() . '/blog/' . $p['slug'] . '/') . '</loc><lastmod>' . e(date('Y-m-d', strtotime($p['updated'] ?? $p['date']))) . "</lastmod></url>\n";
echo '</urlset>';
