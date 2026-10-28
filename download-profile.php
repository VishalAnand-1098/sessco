<?php
/* JASSCO Steel — company profile download, released only after the visitor submits their details.
   POST: validates details, records the lead (Admin > Enquiries), notifies by email + WhatsApp, issues a 24-hour download token.
   GET ?t=TOKEN: streams the PDF from the protected inc/files folder. */
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/mailer.php';
require __DIR__ . '/inc/whatsapp.php';

const PROFILE_PDF = __DIR__ . '/inc/files/JASSCO-Steel-Company-Profile.pdf';
const PROFILE_NAME = 'JASSCO-Steel-Company-Profile.pdf';

function token_dir(): string { $d = data_dir() . '/download-tokens'; if (!is_dir($d)) @mkdir($d, 0750, true); return $d; }
function back(string $err): void { header('Location: /downloads/?error=' . urlencode($err) . '#get', true, 303); exit; }

/* ---------- GET: deliver the file for a valid token ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $t = (string)($_GET['t'] ?? '');
  $f = preg_match('/^[a-f0-9]{32}$/', $t) && data_dir() ? token_dir() . "/$t.json" : '';
  $tok = $f ? json_read($f) : null;
  if (!$tok || ($tok['exp'] ?? 0) < time() || !is_file(PROFILE_PDF)) { header('Location: /downloads/?error=expired#get', true, 303); exit; }
  $tok['downloads'] = ($tok['downloads'] ?? 0) + 1; json_write($f, $tok);
  header('Content-Type: application/pdf');
  header('Content-Disposition: attachment; filename="' . PROFILE_NAME . '"');
  header('Content-Length: ' . filesize(PROFILE_PDF));
  header('Cache-Control: private, no-store');
  header('X-Robots-Tag: noindex');
  readfile(PROFILE_PDF);
  exit;
}

/* ---------- POST: collect details ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /downloads/', true, 303); exit; }
if (!empty($_POST['website'])) back('spam');
$ts = (int)($_POST['ts'] ?? 0);
if ($ts <= 0 || (time() - $ts) < 3 || (time() - $ts) > 86400) back('spam');

$get = function (string $k, int $max): string { $v = trim(preg_replace('/[\r\n\t]+/', ' ', (string)($_POST[$k] ?? '')) ?? ''); return mb_substr($v, 0, $max); };
$data = ['name' => $get('name', 120), 'company' => $get('company', 160), 'email' => $get('email', 160), 'phone' => $get('phone', 40), 'country' => $get('country', 80)];
foreach ($data as $v) if ($v === '') back('invalid');
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) back('invalid');
if (strlen(preg_replace('/\D/', '', $data['phone'])) < 7) back('invalid');
if (empty($_POST['consent'])) back('invalid');
if (!data_dir()) back('send');

$ref = 'DL-' . date('Ymd-His') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
$data['product'] = 'Company profile download';
$labels = ['name' => 'Name', 'company' => 'Company', 'email' => 'Business email', 'phone' => 'Mobile / WhatsApp number', 'country' => 'Country', 'product' => 'Request'];

$s = settings();
$text = "Company profile downloaded from the JASSCO website\nReference: $ref\nReceived: " . date('d M Y, H:i') . " IST\n\n";
foreach ($labels as $k => $l) $text .= "$l: {$data[$k]}\n";
$text .= "\nThis visitor requested the company profile PDF. Reply to this email to follow up.";
$mail = send_mail(explode(',', (string)$s['notify_emails']), 'Profile download: ' . $data['company'] . ' (' . $data['country'] . ') [' . $ref . ']', $text, $data['email'], $data['name']);
$wa = whatsapp_alert("*JASSCO company profile downloaded*\nRef: $ref\nName: {$data['name']}\nCompany: {$data['company']}\nCountry: {$data['country']}\nEmail: {$data['email']}\nPhone: {$data['phone']}", [$data['name'] . ' – ' . $data['company'], $data['country'], 'Company profile download', $ref]);

$dir = data_dir() . '/enquiries/' . $ref; @mkdir($dir, 0750, true);
json_write("$dir/enquiry.json", ['ref' => $ref, 'type' => 'download', 'received' => date('c'), 'data' => $data, 'labels' => $labels, 'files' => [],
  'email' => $mail, 'whatsapp' => $wa, 'status' => 'new', 'ip' => $_SERVER['REMOTE_ADDR'] ?? '', 'page' => 'downloads']);

// clean up expired tokens, then issue a new one valid for 24 hours
foreach (glob(token_dir() . '/*.json') ?: [] as $old) { $o = json_read($old); if (!$o || ($o['exp'] ?? 0) < time()) @unlink($old); }
$t = bin2hex(random_bytes(16));
json_write(token_dir() . "/$t.json", ['ref' => $ref, 'exp' => time() + 86400, 'downloads' => 0]);
header('Location: /downloads/?t=' . $t, true, 303);
exit;
