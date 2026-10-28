<?php
/* Public, read-only site settings used by the home page (factory tour video link). */
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
$v = trim((string)(settings()['tour_video'] ?? ''));
if ($v !== '' && !preg_match('~^(https://|/uploads/)~', $v)) $v = '';
echo json_encode(['tour_video' => $v], JSON_UNESCAPED_SLASHES);
