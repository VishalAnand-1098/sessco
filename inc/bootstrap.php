<?php
/* JASSCO Steel — shared PHP bootstrap: paths, settings, security helpers. */
declare(strict_types=1);
if (defined('JASSCO_BOOT')) return;
define('JASSCO_BOOT', 1);
date_default_timezone_set('Asia/Kolkata');
mb_internal_encoding('UTF-8');

define('PUB', dirname(__DIR__));                     // public_html
define('SITE_URL_FALLBACK', 'https://www.jasscosteel.com');

/* Private data folder: one level ABOVE public_html when possible (never reachable from the web). */
function data_dir(): string {
  static $d = null; if ($d !== null) return $d;
  $cands = [dirname(PUB) . '/jassco-data', PUB . '/_data'];
  foreach ($cands as $c) {
    if (!is_dir($c)) @mkdir($c, 0750, true);
    if (is_dir($c) && is_writable($c)) {
      if (strpos($c, PUB) === 0 && !file_exists($c . '/.htaccess')) @file_put_contents($c . '/.htaccess', "Require all denied\nDeny from all\n");
      foreach (['posts', 'enquiries', 'logs'] as $s) if (!is_dir("$c/$s")) @mkdir("$c/$s", 0750, true);
      return $d = $c;
    }
  }
  return $d = '';
}

/* ---------- Settings (editable from Admin > Settings) ---------- */
function settings_defaults(): array {
  return [
    'site_url'       => SITE_URL_FALLBACK,
    'notify_emails'  => 'sales.jasscosteel@gmail.com',
    'from_email'     => 'no-reply@jasscosteel.com',
    'from_name'      => 'JASSCO Steel website',
    'smtp_host'      => '', 'smtp_port' => '465', 'smtp_secure' => 'ssl', 'smtp_user' => '', 'smtp_pass' => '',
    'wa_provider'    => 'callmebot',            // callmebot | cloudapi | off
    'wa_number'      => '919971315142',         // alert recipient, international format without +
    'wa_callmebot_key' => '',
    'wa_cloud_token' => '', 'wa_cloud_phone_id' => '', 'wa_cloud_template' => '', 'wa_cloud_lang' => 'en',
    'public_whatsapp'=> '919971315142',         // number used by the WhatsApp chat button
    'tour_video'     => '',                     // factory tour video: YouTube / Vimeo link or /uploads/video/....mp4
  ];
}
function settings(): array {
  static $s = null; if ($s !== null) return $s;
  $s = settings_defaults();
  $f = data_dir() . '/settings.json';
  if (data_dir() && is_file($f)) { $j = json_decode((string)file_get_contents($f), true); if (is_array($j)) $s = array_merge($s, $j); }
  return $s;
}
function save_settings(array $new): bool {
  $cur = settings(); $out = array_merge($cur, array_intersect_key($new, settings_defaults()));
  return (bool)@file_put_contents(data_dir() . '/settings.json', json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/* ---------- Helpers ---------- */
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function site_url(): string { return rtrim(settings()['site_url'] ?: SITE_URL_FALLBACK, '/'); }
function log_line(string $file, string $msg): void { if (data_dir()) @file_put_contents(data_dir() . "/logs/$file.log", date('c') . ' ' . $msg . "\n", FILE_APPEND | LOCK_EX); }
function slugify(string $s): string {
  $s = strtolower(trim($s)); $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? ''; return trim(substr($s, 0, 80), '-') ?: 'post';
}
function json_read(string $f): ?array { if (!is_file($f)) return null; $j = json_decode((string)file_get_contents($f), true); return is_array($j) ? $j : null; }
function json_write(string $f, array $d): bool { return (bool)@file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX); }
