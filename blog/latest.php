<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/blog.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
$out = [];
foreach (array_slice(all_posts(true), 0, 3) as $p) $out[] = ['title' => $p['title'], 'url' => '/blog/' . $p['slug'] . '/', 'excerpt' => $p['excerpt'], 'cover' => $p['cover'], 'date' => date('d M Y', strtotime($p['date']))];
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
