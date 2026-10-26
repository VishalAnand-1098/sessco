<?php
/* Blog storage (JSON files in the private data folder) and HTML sanitising. */
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function posts_dir(): string { return data_dir() . '/posts'; }

function seed_posts(): void {
  if (!data_dir() || is_file(data_dir() . '/.seeded')) return;
  foreach (glob(__DIR__ . '/seed/*.json') ?: [] as $f) { $slug = basename($f); if (!is_file(posts_dir() . "/$slug")) @copy($f, posts_dir() . "/$slug"); }
  @touch(data_dir() . '/.seeded');
}

/** @return array<int,array> newest first */
function all_posts(bool $publishedOnly = true): array {
  seed_posts(); $out = [];
  foreach (glob(posts_dir() . '/*.json') ?: [] as $f) {
    $p = json_read($f); if (!$p) continue;
    if ($publishedOnly && (($p['status'] ?? '') !== 'published' || strtotime($p['date'] ?? 'now') > time())) continue;
    $out[] = $p;
  }
  usort($out, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
  return $out;
}
function get_post(string $slug): ?array { $slug = slugify($slug); seed_posts(); return json_read(posts_dir() . "/$slug.json"); }
function save_post(array $p, string $oldSlug = ''): bool {
  if ($oldSlug !== '' && $oldSlug !== $p['slug']) @unlink(posts_dir() . '/' . slugify($oldSlug) . '.json');
  return json_write(posts_dir() . '/' . $p['slug'] . '.json', $p);
}
function delete_post(string $slug): bool { return @unlink(posts_dir() . '/' . slugify($slug) . '.json'); }

function sanitize_html(string $html): string {
  $allowed = ['p'=>[], 'br'=>[], 'h2'=>[], 'h3'=>[], 'h4'=>[], 'strong'=>[], 'b'=>[], 'em'=>[], 'i'=>[], 'u'=>[], 'ul'=>[], 'ol'=>[], 'li'=>[],
    'blockquote'=>[], 'a'=>['href','title'], 'img'=>['src','alt','width','height'], 'figure'=>[], 'figcaption'=>[],
    'table'=>[], 'thead'=>[], 'tbody'=>[], 'tr'=>[], 'th'=>[], 'td'=>[], 'hr'=>[]];
  $html = trim($html); if ($html === '') return '';
  $doc = new DOMDocument();
  libxml_use_internal_errors(true);
  $doc->loadHTML('<?xml encoding="utf-8"?><div id="root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
  libxml_clear_errors();
  $root = $doc->getElementById('root'); if (!$root) return '';
  $walk = function (DOMNode $node) use (&$walk, $allowed, $doc) {
    for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
      $c = $node->childNodes->item($i);
      if ($c instanceof DOMElement) {
        $tag = strtolower($c->tagName);
        if ($tag === 'div' || $tag === 'span' || $tag === 'font') {  // unwrap
          $walk($c); while ($c->firstChild) $node->insertBefore($c->firstChild, $c);
          if ($tag === 'div') $node->insertBefore($doc->createElement('br'), $c);
          $node->removeChild($c); continue;
        }
        if (!isset($allowed[$tag])) { if (in_array($tag, ['script','style','iframe','object','embed','form','input','button','svg','math'], true)) { $node->removeChild($c); continue; }
          $walk($c); while ($c->firstChild) $node->insertBefore($c->firstChild, $c); $node->removeChild($c); continue; }
        foreach (iterator_to_array($c->attributes) as $a) if (!in_array(strtolower($a->name), $allowed[$tag], true)) $c->removeAttribute($a->name);
        if ($tag === 'a') {
          $h = trim($c->getAttribute('href'));
          if (!preg_match('~^(https?://|mailto:|tel:|/|#)~i', $h)) $c->removeAttribute('href');
          elseif (preg_match('~^https?://~i', $h) && stripos($h, (string)parse_url(site_url(), PHP_URL_HOST)) === false) $c->setAttribute('rel', 'noopener');
        }
        if ($tag === 'img') { $s = trim($c->getAttribute('src')); if (!preg_match('~^(/uploads/|/assets/|https://)~', $s)) { $node->removeChild($c); continue; } $c->setAttribute('loading', 'lazy'); }
        $walk($c);
      } elseif ($c instanceof DOMComment) { $node->removeChild($c); }
    }
  };
  $walk($root);
  $out = ''; foreach ($root->childNodes as $c) $out .= $doc->saveHTML($c);
  $out = preg_replace('~(<br\s*/?>\s*){3,}~i', '<br><br>', $out) ?? $out;
  return trim($out);
}

/** Save an uploaded image (re-encoded, max 1600px wide). @return string public URL or '' */
function save_image_upload(array $file): string {
  if (($file['error'] ?? 1) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 8 * 1048576 || !is_uploaded_file($file['tmp_name'])) return '';
  $info = @getimagesize($file['tmp_name']); if (!$info) return '';
  $type = $info[2];
  $src = match ($type) { IMAGETYPE_JPEG => @imagecreatefromjpeg($file['tmp_name']), IMAGETYPE_PNG => @imagecreatefrompng($file['tmp_name']),
    IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file['tmp_name']) : false, default => false };
  if (!$src) return '';
  $w = imagesx($src); $h = imagesy($src); $nw = min(1600, $w); $nh = (int)round($h * $nw / $w);
  $dst = imagecreatetruecolor($nw, $nh); imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
  imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
  $dir = PUB . '/uploads/blog/' . date('Y/m'); if (!is_dir($dir)) @mkdir($dir, 0755, true);
  $base = slugify(pathinfo((string)$file['name'], PATHINFO_FILENAME)) . '-' . substr(bin2hex(random_bytes(3)), 0, 5);
  if (function_exists('imagewebp')) { $name = "$base.webp"; $ok = imagewebp($dst, "$dir/$name", 82); }
  else { $name = "$base.jpg"; $ok = imagejpeg($dst, "$dir/$name", 85); }
  imagedestroy($src); imagedestroy($dst);
  return $ok ? '/uploads/blog/' . date('Y/m') . '/' . $name : '';
}

function reading_time(string $html): int { return max(1, (int)round(str_word_count(strip_tags($html)) / 220)); }
