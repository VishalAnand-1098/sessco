<?php
/* JASSCO Steel — RFQ and contact form handler.
   Each enquiry is: (1) saved privately on the server (visible in Admin > Enquiries),
   (2) emailed to the addresses in Admin > Settings, (3) sent as a WhatsApp alert to the number in Admin > Settings. */
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/mailer.php';
require __DIR__ . '/inc/whatsapp.php';

function back(string $err): void {
  $ref = ($_POST['form_type'] ?? '') === 'contact' ? '/contact/' : '/request-a-quote/';
  header('Location: ' . $ref . '?error=' . urlencode($err) . '#form', true, 303); exit;
}
function clean(string $k, int $max = 2000): string {
  $v = trim((string)($_POST[$k] ?? '')); $v = str_replace(["\r\n", "\r"], "\n", $v);
  $v = preg_replace('/[^\P{C}\n\t]/u', '', $v) ?? ''; return mb_substr($v, 0, $max);
}
function oneline(string $v): string { return trim(preg_replace('/[\r\n]+/', ' ', $v) ?? ''); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /request-a-quote/', true, 303); exit; }
if (!empty($_POST['website'])) back('spam');                         // honeypot
$ts = (int)($_POST['ts'] ?? 0);
if ($ts <= 0 || (time() - $ts) < 4 || (time() - $ts) > 86400) back('spam');   // filled too fast / stale

$type = ($_POST['form_type'] ?? 'rfq') === 'contact' ? 'contact' : 'rfq';
$fields = ['name' => ['Name', 120, true], 'company' => ['Company', 160, true], 'email' => ['Business email', 160, true],
           'country' => ['Country', 80, true], 'phone' => ['Mobile / WhatsApp number', 40, true]];
if ($type === 'rfq') {
  $fields += ['product' => ['Product / process', 120, true], 'material' => ['Material grade', 160, false],
    'dimensions' => ['Thickness and dimensions', 400, false], 'quantity' => ['Estimated quantity', 160, false],
    'frequency' => ['Order frequency', 80, false], 'finish' => ['Required finish', 160, false],
    'delivery_location' => ['Delivery location', 160, false], 'delivery_date' => ['Target delivery date', 60, false],
    'standards' => ['Applicable standards', 300, false], 'message' => ['Additional requirements', 4000, false]];
} else {
  $fields += ['message' => ['Message', 4000, true]];
}
$data = [];
foreach ($fields as $k => [$label, $max, $req]) {
  $v = clean($k, $max); if ($k !== 'message') $v = oneline($v);
  if ($k === 'product' && $v !== '') $v = ucwords(str_replace('-', ' ', $v));
  if ($req && $v === '') back('invalid');
  $data[$k] = $v;
}
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) back('invalid');
if (strlen(preg_replace('/\D/', '', $data['phone'])) < 7) back('invalid');
if (empty($_POST['consent'])) back('invalid');

// --- uploads (RFQ only) ---
$uploads = []; $allowed = ['pdf','dxf','dwg','step','stp','iges','igs','jpg','jpeg','png','zip'];
if ($type === 'rfq' && !empty($_FILES['drawings']) && is_array($_FILES['drawings']['name'])) {
  $total = 0; $n = count($_FILES['drawings']['name']); if ($n > 5) back('file');
  for ($i = 0; $i < $n; $i++) {
    $ec = $_FILES['drawings']['error'][$i]; if ($ec === UPLOAD_ERR_NO_FILE) continue; if ($ec !== UPLOAD_ERR_OK) back('file');
    $tmp = $_FILES['drawings']['tmp_name'][$i]; $size = (int)$_FILES['drawings']['size'][$i];
    $orig = basename((string)$_FILES['drawings']['name'][$i]); $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true) || $size <= 0 || $size > 10 * 1048576 || !is_uploaded_file($tmp)) back('file');
    $total += $size; if ($total > 20 * 1048576) back('file');
    $safe = substr(preg_replace('/[^A-Za-z0-9._-]+/', '_', pathinfo($orig, PATHINFO_FILENAME)) ?? 'file', 0, 80) . '.' . $ext;
    $uploads[] = ['tmp' => $tmp, 'name' => $safe];
  }
}

// --- save privately ---
$ref = strtoupper($type) . '-' . date('Ymd-His') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
$files = []; $dir = data_dir() ? data_dir() . '/enquiries/' . $ref : '';
if ($dir) {
  @mkdir($dir, 0750, true);
  foreach ($uploads as $u) { $dest = "$dir/{$u['name']}"; if (@move_uploaded_file($u['tmp'], $dest)) $files[] = ['path' => $dest, 'name' => $u['name']]; }
} else {
  foreach ($uploads as $u) $files[] = ['path' => $u['tmp'], 'name' => $u['name']];
}

// --- email ---
$s = settings();
$lines = ["New " . ($type === 'rfq' ? 'request for quotation' : 'contact enquiry') . " from the JASSCO website", "Reference: $ref", 'Received: ' . date('d M Y, H:i') . ' IST', ''];
foreach ($fields as $k => [$label]) if ($data[$k] !== '') $lines[] = ($k === 'message' ? "\n$label:\n" : "$label: ") . $data[$k];
if ($files) { $lines[] = ''; $lines[] = 'Attachments: ' . implode(', ', array_column($files, 'name')); }
$lines[] = ''; $lines[] = 'Reply to this email to answer the customer directly.';
$lines[] = 'All enquiries are also stored in the website admin: ' . site_url() . '/admin/';
$text = implode("\n", $lines);
$subject = ($type === 'rfq' ? 'New RFQ' : 'New enquiry') . ': ' . $data['company'] . ' (' . $data['country'] . ') [' . $ref . ']';
$mail = send_mail(explode(',', (string)$s['notify_emails']), $subject, $text, $data['email'], $data['name'], $files);

// --- WhatsApp alert ---
$wa_text = "*New JASSCO website " . ($type === 'rfq' ? 'RFQ' : 'enquiry') . "*\nRef: $ref\nName: {$data['name']}\nCompany: {$data['company']}\nCountry: {$data['country']}\nEmail: {$data['email']}"
  . ($data['phone'] ? "\nPhone: {$data['phone']}" : '') . (isset($data['product']) ? "\nProduct: {$data['product']}" : '')
  . (!empty($data['quantity']) ? "\nQty: {$data['quantity']}" : '') . ($files ? "\nFiles: " . count($files) : '')
  . ($data['message'] !== '' ? "\nMessage: " . mb_substr($data['message'], 0, 400) : '');
$wa = whatsapp_alert($wa_text, [$data['name'] . ' – ' . $data['company'], $data['country'], $data['product'] ?? 'Contact enquiry', $ref]);

// --- record ---
if ($dir) {
  json_write("$dir/enquiry.json", ['ref' => $ref, 'type' => $type, 'received' => date('c'), 'data' => $data,
    'labels' => array_map(fn($f) => $f[0], $fields), 'files' => array_column($files, 'name'),
    'email' => $mail, 'whatsapp' => $wa, 'status' => 'new', 'ip' => $_SERVER['REMOTE_ADDR'] ?? '', 'page' => oneline(clean('page', 200))]);
}
if (!$mail['ok']) log_line('mail', "$ref " . $mail['error']);
if (!$wa['ok']) log_line('whatsapp', "$ref " . $wa['error']);

if (!$mail['ok'] && !$dir) back('send');   // nothing delivered and nothing saved
header('Location: /thank-you/?ref=' . urlencode($ref), true, 303);
exit;
