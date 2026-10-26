<?php
/* JASSCO Steel — website admin: blog posts, enquiries, settings. */
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/blog.php';
require __DIR__ . '/../inc/mailer.php';
require __DIR__ . '/../inc/whatsapp.php';

const ADMIN_CSS = <<<'CSS'
*{box-sizing:border-box}body{margin:0;font:15px/1.55 system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;color:#1D2939;background:#F4F6F9}
a{color:#154A8B}h1{font-size:26px;margin:0 0 18px;color:#101828}h2{font-size:18px;margin:32px 0 12px}
.app{display:grid;grid-template-columns:230px 1fr;min-height:100vh}.side{background:#08162C;padding:20px 14px;position:sticky;top:0;height:100vh}
.side .logo{display:block;padding:4px 8px 22px}.side nav a{display:block;color:#C9D4E3;text-decoration:none;padding:10px 12px;border-radius:6px;margin-bottom:2px}
.side nav a:hover,.side nav a.on{background:#154A8B;color:#fff}main{padding:32px clamp(16px,4vw,48px);max-width:1180px;width:100%}
@media(max-width:800px){.app{grid-template-columns:1fr}.side{position:static;height:auto}.side nav{display:flex;flex-wrap:wrap;gap:4px}.side nav a{padding:8px 10px;font-size:14px}}
.bare main{max-width:440px;margin:8vh auto}.login{background:#fff;border:1px solid #E4E7EC;border-radius:10px;padding:28px}
.btn{display:inline-flex;align-items:center;gap:6px;background:#154A8B;color:#fff;border:2px solid #154A8B;border-radius:6px;padding:10px 16px;font:600 14px system-ui;text-decoration:none;cursor:pointer}
.btn:hover{background:#0F3A6E}.btn.ghost{background:#fff;color:#154A8B}.btn.ghost:hover{background:#EEF3FA}.btn.sm{padding:6px 12px;font-size:13px}
.link{background:none;border:0;color:#B42318;cursor:pointer;font:inherit;padding:0}.row{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:0 0 20px}
.form{display:grid;gap:14px}.form label,.lbl{display:grid;gap:6px;font-weight:600;font-size:14px;color:#101828}
input,select,textarea{font:inherit;font-weight:400;padding:10px 12px;border:1px solid #D0D5DD;border-radius:6px;background:#fff;width:100%}
input[type=checkbox]{width:auto}input:focus,select:focus,textarea:focus,.ed:focus{outline:3px solid rgba(21,74,139,.2);border-color:#154A8B}
fieldset{border:1px solid #E4E7EC;border-radius:10px;padding:18px;background:#fff;display:grid;gap:14px}legend{font-weight:700;padding:0 6px}
details summary{cursor:pointer;font-weight:600;color:#154A8B}details[open]{display:grid;gap:12px}.box{background:#F4F6F9;border-radius:8px;padding:14px}
.note{padding:12px 14px;border-radius:6px;margin:0 0 16px}.note.ok{background:#ECFDF3;color:#067647;border:1px solid #ABEFC6}.note.err{background:#FEF3F2;color:#B42318;border:1px solid #FECDCA}.note.warn{background:#FFF8E6;color:#5C4400;border:1px solid #F5DFA0}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}.stats a{background:#fff;border:1px solid #E4E7EC;border-radius:10px;padding:18px;text-decoration:none;color:#475467}
.stats b{display:block;font-size:30px;color:#154A8B}@media(max-width:700px){.stats{grid-template-columns:1fr 1fr}}
.tbl{width:100%;border-collapse:collapse;background:#fff;border:1px solid #E4E7EC;border-radius:10px;overflow:hidden}.tbl th,.tbl td{text-align:left;padding:11px 14px;border-bottom:1px solid #E4E7EC;vertical-align:top}
.tbl thead th{background:#F9FAFB;font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#667085}.tbl tr.new td{background:#F5F9FF}.tbl.kv th{width:220px;color:#475467;font-weight:500}
.act form{display:inline}.muted{color:#667085}.small{font-size:13px}.pill{display:inline-block;padding:2px 8px;border-radius:99px;font-size:12px;font-weight:600;background:#EEF3FA;color:#154A8B}
.pill.new{background:#FFF8E6;color:#8A6100}.pill.won,.pill.quoted{background:#ECFDF3;color:#067647}.pill.closed{background:#F2F4F7;color:#475467}
.tb{display:flex;flex-wrap:wrap;gap:4px;background:#fff;border:1px solid #D0D5DD;border-bottom:0;border-radius:6px 6px 0 0;padding:6px}.tb button{background:#F4F6F9;border:1px solid #E4E7EC;border-radius:4px;padding:6px 10px;cursor:pointer;font:13px system-ui}
.tb button:hover{background:#EEF3FA}.ed{min-height:380px;background:#fff;border:1px solid #D0D5DD;border-radius:0 0 6px 6px;padding:18px 22px;line-height:1.7;font-size:16px;margin-top:-14px}
.ed h2{font-size:22px}.ed h3{font-size:18px}.ed img{max-width:100%;height:auto;border-radius:6px}.ed blockquote{border-left:4px solid #F5B800;margin:0;padding:4px 16px;color:#475467}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}@media(max-width:700px){.grid2{grid-template-columns:1fr}}.cov{max-width:100%;max-height:180px;border-radius:6px;display:block}
.chk{display:flex!important;gap:8px;align-items:center;font-weight:400!important}.sticky{position:sticky;bottom:0;background:#F4F6F9;padding:12px 0;border-top:1px solid #E4E7EC;margin:0}
code{background:#EEF3FA;padding:2px 6px;border-radius:4px}
CSS;

header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_name('jassco_admin');
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'cookie_secure' => $https, 'use_strict_mode' => true]);

if (!data_dir()) { http_response_code(500); exit('The website cannot create its private data folder. In cPanel File Manager, create a folder named "jassco-data" next to public_html (not inside it).'); }

$authFile = data_dir() . '/admin.json';
$auth = json_read($authFile);
$p = (string)($_GET['p'] ?? 'dashboard');
$msg = ''; $err = '';

function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf() . '">'; }
function check_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) { http_response_code(400); exit('Session expired. Go back, refresh the page and try again.'); } }
function go(string $to): void { header('Location: ' . $to, true, 303); exit; }
function flash(string $m, string $type = 'ok'): void { $_SESSION['flash'] = [$m, $type]; }
function throttle_file(): string { return data_dir() . '/logs/login-' . md5($_SERVER['REMOTE_ADDR'] ?? 'x') . '.json'; }

/* ---------- First-run setup ---------- */
if (!$auth) {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $u = trim((string)($_POST['user'] ?? '')); $pw = (string)($_POST['pass'] ?? ''); $pw2 = (string)($_POST['pass2'] ?? '');
    if (!preg_match('/^[A-Za-z0-9._@-]{3,60}$/', $u)) $err = 'Choose a username of 3–60 letters or numbers.';
    elseif (strlen($pw) < 10) $err = 'Use a password of at least 10 characters.';
    elseif ($pw !== $pw2) $err = 'The two passwords do not match.';
    else { json_write($authFile, ['user' => $u, 'hash' => password_hash($pw, PASSWORD_DEFAULT), 'created' => date('c')]); session_regenerate_id(true); $_SESSION['admin'] = $u; flash('Admin account created. Welcome!'); go('/admin/'); }
  }
  admin_shell('Create admin account', '<div class="login"><h1>Set up your admin account</h1><p class="muted">This is the first visit to the admin panel. Create the username and password you will use to manage the blog and enquiries. Keep them safe.</p>'
    . ($err ? '<div class="note err">' . e($err) . '</div>' : '')
    . '<form method="post" class="form">' . csrf_field() . '<label>Username<input name="user" required autocomplete="username"></label><label>Password (min. 10 characters)<input type="password" name="pass" required autocomplete="new-password"></label><label>Repeat password<input type="password" name="pass2" required autocomplete="new-password"></label><button class="btn">Create account</button></form></div>', false);
  exit;
}

/* ---------- Login / logout ---------- */
if ($p === 'logout') { $_SESSION = []; session_destroy(); go('/admin/'); }
if (empty($_SESSION['admin'])) {
  $t = json_read(throttle_file()) ?? ['n' => 0, 't' => 0];
  if ($t['t'] < time() - 900) $t = ['n' => 0, 't' => time()];
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if ($t['n'] >= 6) $err = 'Too many attempts. Please wait 15 minutes and try again.';
    elseif (hash_equals($auth['user'], trim((string)($_POST['user'] ?? ''))) && password_verify((string)($_POST['pass'] ?? ''), $auth['hash'])) {
      @unlink(throttle_file()); session_regenerate_id(true); $_SESSION['admin'] = $auth['user']; go('/admin/' . (isset($_GET['next']) ? '?' . preg_replace('/[^a-z0-9=&_-]/i', '', (string)$_GET['next']) : ''));
    } else { $t['n']++; json_write(throttle_file(), $t); $err = 'Incorrect username or password.'; usleep(600000); }
  }
  admin_shell('Log in', '<div class="login"><h1>JASSCO admin</h1>' . ($err ? '<div class="note err">' . e($err) . '</div>' : '')
    . '<form method="post" class="form">' . csrf_field() . '<label>Username<input name="user" required autocomplete="username" autofocus></label><label>Password<input type="password" name="pass" required autocomplete="current-password"></label><button class="btn">Log in</button></form>'
    . '<p class="muted small">Forgot the password? In cPanel File Manager, delete the file <code>jassco-data/admin.json</code> (next to public_html) and open this page again to create a new account.</p></div>', false);
  exit;
}

/* ---------- Logged-in routes ---------- */
if (isset($_SESSION['flash'])) { [$msg, $mt] = $_SESSION['flash']; unset($_SESSION['flash']); $msg = '<div class="note ' . ($mt === 'err' ? 'err' : 'ok') . '">' . e($msg) . '</div>'; }

switch ($p) {
  case 'posts': page_posts($msg); break;
  case 'edit': page_edit($msg); break;
  case 'enquiries': page_enquiries($msg); break;
  case 'enquiry': page_enquiry($msg); break;
  case 'file': serve_file(); break;
  case 'settings': page_settings($msg); break;
  case 'password': page_password($msg); break;
  case 'upload': ajax_upload(); break;
  default: page_dashboard($msg);
}
exit;

/* ================= PAGES ================= */
function enquiries_list(): array {
  $out = [];
  foreach (glob(data_dir() . '/enquiries/*/enquiry.json') ?: [] as $f) { $j = json_read($f); if ($j) $out[] = $j; }
  // older format (plain .txt from first version)
  usort($out, fn($a, $b) => strcmp($b['received'], $a['received']));
  return $out;
}

function page_dashboard(string $msg): void {
  $posts = all_posts(false); $pub = count(array_filter($posts, fn($x) => $x['status'] === 'published'));
  $enq = enquiries_list(); $new = count(array_filter($enq, fn($x) => ($x['status'] ?? 'new') === 'new'));
  $s = settings();
  $warn = '';
  if ($s['wa_provider'] === 'callmebot' && trim($s['wa_callmebot_key']) === '') $warn .= '<div class="note warn">WhatsApp alerts are not active yet. Open <a href="?p=settings#wa">Settings → WhatsApp</a> and add the API key (2-minute setup).</div>';
  $rows = '';
  foreach (array_slice($enq, 0, 6) as $q) $rows .= enquiry_row($q);
  $html = $msg . $warn . '<h1>Dashboard</h1><div class="stats"><a href="?p=enquiries"><b>' . $new . '</b><span>New enquiries</span></a><a href="?p=enquiries"><b>' . count($enq) . '</b><span>Total enquiries</span></a><a href="?p=posts"><b>' . $pub . '</b><span>Published articles</span></a><a href="?p=posts"><b>' . (count($posts) - $pub) . '</b><span>Drafts</span></a></div>'
    . '<div class="row"><a class="btn" href="?p=edit">+ Write a new article</a><a class="btn ghost" href="/blog/" target="_blank">View blog</a></div>'
    . '<h2>Latest enquiries</h2>' . ($rows ? '<table class="tbl"><thead><tr><th>Received</th><th>From</th><th>Product / message</th><th>Status</th><th></th></tr></thead><tbody>' . $rows . '</tbody></table>' : '<p class="muted">No enquiries yet.</p>');
  admin_shell('Dashboard', $html);
}

function enquiry_row(array $q): string {
  $d = $q['data']; $st = $q['status'] ?? 'new';
  $what = $d['product'] ?? mb_substr($d['message'] ?? '', 0, 60);
  $notif = (($q['email']['ok'] ?? false) ? '✉️' : '⚠️✉️') . ' ' . (($q['whatsapp']['ok'] ?? false) ? '🟢WA' : '');
  return '<tr class="' . ($st === 'new' ? 'new' : '') . '"><td>' . e(date('d M Y H:i', strtotime($q['received']))) . '</td><td><strong>' . e($d['name']) . '</strong><br><span class="muted">' . e($d['company']) . ' · ' . e($d['country']) . '</span></td><td>' . e($what) . (!empty($q['files']) ? ' <span class="pill">' . count($q['files']) . ' file(s)</span>' : '') . '</td><td><span class="pill ' . e($st) . '">' . e(ucfirst($st)) . '</span><br><span class="small muted">' . $notif . '</span></td><td><a href="?p=enquiry&ref=' . e($q['ref']) . '">Open</a></td></tr>';
}

function page_enquiries(string $msg): void {
  $enq = enquiries_list(); $f = (string)($_GET['status'] ?? '');
  if ($f) $enq = array_filter($enq, fn($x) => ($x['status'] ?? 'new') === $f);
  if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="jassco-enquiries-' . date('Ymd') . '.csv"');
    $o = fopen('php://output', 'w'); fputcsv($o, ['Reference', 'Received', 'Status', 'Name', 'Company', 'Email', 'Country', 'Phone', 'Product', 'Quantity', 'Message']);
    foreach (enquiries_list() as $q) { $d = $q['data']; fputcsv($o, [$q['ref'], $q['received'], $q['status'] ?? 'new', $d['name'], $d['company'], $d['email'], $d['country'], $d['phone'] ?? '', $d['product'] ?? '', $d['quantity'] ?? '', $d['message'] ?? '']); }
    exit;
  }
  $rows = ''; foreach ($enq as $q) $rows .= enquiry_row($q);
  admin_shell('Enquiries', $msg . '<h1>Enquiries</h1><div class="row"><a class="btn ghost sm" href="?p=enquiries">All</a><a class="btn ghost sm" href="?p=enquiries&status=new">New</a><a class="btn ghost sm" href="?p=enquiries&status=contacted">Contacted</a><a class="btn ghost sm" href="?p=enquiries&status=quoted">Quoted</a><a class="btn ghost sm" href="?p=enquiries&status=closed">Closed</a><a class="btn sm" href="?p=enquiries&export=1">Download Excel (CSV)</a></div>'
    . ($rows ? '<table class="tbl"><thead><tr><th>Received</th><th>From</th><th>Product / message</th><th>Status</th><th></th></tr></thead><tbody>' . $rows . '</tbody></table>' : '<p class="muted">No enquiries here yet.</p>'));
}

function find_enquiry(string $ref): ?array { $ref = preg_replace('/[^A-Z0-9-]/', '', $ref) ?? ''; return $ref ? json_read(data_dir() . "/enquiries/$ref/enquiry.json") : null; }

function page_enquiry(string $msg): void {
  $q = find_enquiry((string)($_GET['ref'] ?? '')); if (!$q) { admin_shell('Not found', '<p>Enquiry not found.</p>'); return; }
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (isset($_POST['status'])) { $q['status'] = in_array($_POST['status'], ['new', 'contacted', 'quoted', 'won', 'closed'], true) ? $_POST['status'] : 'new'; $q['notes'] = mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 4000); json_write(data_dir() . "/enquiries/{$q['ref']}/enquiry.json", $q); flash('Saved.'); }
    if (isset($_POST['resend'])) {
      $r = whatsapp_alert("JASSCO enquiry {$q['ref']} (re-sent)\n" . $q['data']['name'] . ' – ' . $q['data']['company'] . "\n" . ($q['data']['product'] ?? ''), [$q['data']['name'], $q['data']['country'], $q['data']['product'] ?? '', $q['ref']]);
      flash($r['ok'] ? 'WhatsApp alert sent.' : 'WhatsApp failed: ' . $r['error'], $r['ok'] ? 'ok' : 'err');
    }
    go('?p=enquiry&ref=' . $q['ref']);
  }
  if (($q['status'] ?? 'new') === 'new') { $q['status'] = 'seen'; json_write(data_dir() . "/enquiries/{$q['ref']}/enquiry.json", $q); }
  $rows = ''; foreach ($q['data'] as $k => $v) if ($v !== '') $rows .= '<tr><th>' . e($q['labels'][$k] ?? $k) . '</th><td>' . nl2br(e($v)) . '</td></tr>';
  $files = ''; foreach ($q['files'] ?? [] as $f) $files .= '<li><a href="?p=file&ref=' . e($q['ref']) . '&f=' . rawurlencode($f) . '">' . e($f) . '</a></li>';
  $wa = preg_replace('/\D/', '', $q['data']['phone'] ?? '');
  $opts = ''; foreach (['new', 'seen', 'contacted', 'quoted', 'won', 'closed'] as $o) $opts .= '<option' . (($q['status'] ?? '') === $o ? ' selected' : '') . ' value="' . $o . '">' . ucfirst($o) . '</option>';
  $html = $msg . '<p><a href="?p=enquiries">← All enquiries</a></p><h1>' . e($q['data']['company']) . '</h1><p class="muted">Reference ' . e($q['ref']) . ' · received ' . e(date('d M Y, H:i', strtotime($q['received']))) . '</p>'
    . '<div class="row"><a class="btn" href="mailto:' . e($q['data']['email']) . '?subject=' . rawurlencode('Your enquiry to JASSCO Steel [' . $q['ref'] . ']') . '">Reply by email</a>' . ($wa ? '<a class="btn ghost" target="_blank" href="https://wa.me/' . e($wa) . '">WhatsApp customer</a>' : '') . '</div>'
    . '<table class="tbl kv">' . $rows . '</table>' . ($files ? '<h2>Attached files</h2><ul>' . $files . '</ul>' : '')
    . '<h2>Notifications</h2><p>Email: ' . (($q['email']['ok'] ?? false) ? 'sent ✔' : 'failed – ' . e($q['email']['error'] ?? '')) . '<br>WhatsApp: ' . (($q['whatsapp']['ok'] ?? false) ? 'sent ✔' : 'not sent – ' . e($q['whatsapp']['error'] ?? '')) . '</p>'
    . '<form method="post" class="form" style="max-width:640px">' . csrf_field() . '<label>Status<select name="status">' . $opts . '</select></label><label>Internal notes<textarea name="notes" rows="4">' . e($q['notes'] ?? '') . '</textarea></label><div class="row"><button class="btn">Save</button><button class="btn ghost" name="resend" value="1">Re-send WhatsApp alert</button></div></form>';
  admin_shell('Enquiry ' . $q['ref'], $html);
}

function serve_file(): void {
  $q = find_enquiry((string)($_GET['ref'] ?? '')); $f = basename((string)($_GET['f'] ?? ''));
  if (!$q || !in_array($f, $q['files'] ?? [], true)) { http_response_code(404); exit('Not found'); }
  $path = data_dir() . "/enquiries/{$q['ref']}/$f";
  header('Content-Type: application/octet-stream'); header('Content-Disposition: attachment; filename="' . $f . '"'); header('Content-Length: ' . filesize($path)); readfile($path); exit;
}

function page_posts(string $msg): void {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') { check_csrf(); if (!empty($_POST['delete'])) { delete_post((string)$_POST['delete']); flash('Article deleted.'); } go('?p=posts'); }
  $rows = '';
  foreach (all_posts(false) as $x) {
    $rows .= '<tr><td><strong><a href="?p=edit&slug=' . e($x['slug']) . '">' . e($x['title']) . '</a></strong><br><span class="muted small">/blog/' . e($x['slug']) . '/</span></td><td>' . e(date('d M Y', strtotime($x['date']))) . '</td><td><span class="pill ' . ($x['status'] === 'published' ? 'won' : 'new') . '">' . ($x['status'] === 'published' ? 'Published' : 'Draft') . '</span></td><td class="act"><a href="?p=edit&slug=' . e($x['slug']) . '">Edit</a> · <a target="_blank" href="/blog/' . e($x['slug']) . '/">View</a> · <form method="post" onsubmit="return confirm(\'Delete this article permanently?\')">' . csrf_field() . '<button class="link" name="delete" value="' . e($x['slug']) . '">Delete</button></form></td></tr>';
  }
  admin_shell('Blog articles', $msg . '<h1>Blog articles</h1><div class="row"><a class="btn" href="?p=edit">+ Write a new article</a></div>' . ($rows ? '<table class="tbl"><thead><tr><th>Title</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody>' . $rows . '</tbody></table>' : '<p class="muted">No articles yet.</p>'));
}

function page_edit(string $msg): void {
  $slug = (string)($_GET['slug'] ?? ''); $post = $slug ? get_post($slug) : null; $err = '';
  $post = $post ?? ['title' => '', 'slug' => '', 'excerpt' => '', 'content' => '', 'cover' => '', 'cover_alt' => '', 'status' => 'draft', 'date' => date('Y-m-d\TH:i'), 'meta_title' => '', 'meta_description' => '', 'author' => 'JASSCO Steel'];
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $old = $post['slug'];
    $post['title'] = mb_substr(trim((string)$_POST['title']), 0, 160);
    $post['slug'] = slugify((string)($_POST['slug'] ?: $_POST['title']));
    $post['excerpt'] = mb_substr(trim((string)$_POST['excerpt']), 0, 300);
    $post['content'] = sanitize_html((string)$_POST['content']);
    $post['cover_alt'] = mb_substr(trim((string)$_POST['cover_alt']), 0, 160);
    $post['meta_title'] = mb_substr(trim((string)$_POST['meta_title']), 0, 70);
    $post['meta_description'] = mb_substr(trim((string)$_POST['meta_description']), 0, 170);
    $post['author'] = mb_substr(trim((string)$_POST['author']), 0, 80) ?: 'JASSCO Steel';
    $post['date'] = date('Y-m-d\TH:i', strtotime((string)$_POST['date']) ?: time());
    $post['status'] = ($_POST['action'] ?? '') === 'publish' ? 'published' : (($_POST['action'] ?? '') === 'unpublish' ? 'draft' : $post['status']);
    $post['updated'] = date('c');
    if (!empty($_FILES['cover']['name'])) { $u = save_image_upload($_FILES['cover']); if ($u) $post['cover'] = $u; else $err = 'Cover image could not be used. Upload a JPG, PNG or WebP under 8 MB.'; }
    if (!empty($_POST['remove_cover'])) $post['cover'] = '';
    if ($post['title'] === '') $err = 'Please add a title.';
    elseif ($old !== $post['slug'] && get_post($post['slug'])) $err = 'Another article already uses this web address. Change the URL slug.';
    if (!$err) { save_post($post, $old); flash($post['status'] === 'published' ? 'Article saved and live on the blog.' : 'Draft saved.'); go('?p=edit&slug=' . $post['slug']); }
  }
  $isNew = $slug === '';
  $live = $post['status'] === 'published';
  $html = $msg . ($err ? '<div class="note err">' . e($err) . '</div>' : '') . '<p><a href="?p=posts">← All articles</a></p><h1>' . ($isNew ? 'New article' : 'Edit article') . '</h1>'
   . '<form method="post" enctype="multipart/form-data" class="form editor-form" id="ef">' . csrf_field()
   . '<label>Title<input name="title" value="' . e($post['title']) . '" required maxlength="160"></label>'
   . '<label>Short summary (shown on the blog list and under the title)<textarea name="excerpt" rows="2" maxlength="300">' . e($post['excerpt']) . '</textarea></label>'
   . '<div class="lbl">Article</div><div class="tb" role="toolbar">'
   . '<button type="button" data-c="formatBlock" data-v="H2">Heading</button><button type="button" data-c="formatBlock" data-v="H3">Sub-heading</button><button type="button" data-c="formatBlock" data-v="P">Paragraph</button>'
   . '<button type="button" data-c="bold"><b>B</b></button><button type="button" data-c="italic"><i>I</i></button><button type="button" data-c="insertUnorderedList">• List</button><button type="button" data-c="insertOrderedList">1. List</button>'
   . '<button type="button" data-c="formatBlock" data-v="BLOCKQUOTE">Quote</button><button type="button" id="lnk">Link</button><button type="button" id="img">Image</button><button type="button" data-c="removeFormat">Clear format</button><input type="file" id="imgf" accept="image/jpeg,image/png,image/webp" hidden></div>'
   . '<div id="ed" class="ed" contenteditable="true">' . ($post['content'] ?: '<p>Start writing here…</p>') . '</div><input type="hidden" name="content" id="content">'
   . '<div class="grid2"><div><div class="lbl">Cover image</div>' . ($post['cover'] ? '<img src="' . e($post['cover']) . '" class="cov"><label class="chk"><input type="checkbox" name="remove_cover" value="1"> Remove cover</label>' : '') . '<input type="file" name="cover" accept="image/jpeg,image/png,image/webp"><label>Cover image description (for Google &amp; accessibility)<input name="cover_alt" value="' . e($post['cover_alt']) . '"></label></div>'
   . '<div><label>Publish date<input type="datetime-local" name="date" value="' . e(substr($post['date'], 0, 16)) . '"></label><label>Author<input name="author" value="' . e($post['author']) . '"></label><label>Web address (URL slug)<input name="slug" value="' . e($post['slug']) . '" placeholder="created from the title"></label></div></div>'
   . '<details class="seo"><summary>Google search settings (optional)</summary><label>SEO title (max 65 characters)<input name="meta_title" maxlength="70" value="' . e($post['meta_title']) . '"></label><label>Meta description (max 160 characters)<textarea name="meta_description" rows="2" maxlength="170">' . e($post['meta_description']) . '</textarea></label></details>'
   . '<div class="row" style="margin-top:8px"><span class="pill ' . ($live ? 'won' : 'new') . '">' . ($live ? 'Live on blog' : 'Draft') . '</span><button class="btn ghost" name="action" value="save">Save</button>'
   . ($live ? '<button class="btn ghost" name="action" value="unpublish">Unpublish</button>' : '<button class="btn" name="action" value="publish">Publish</button>')
   . (!$isNew ? '<a class="btn ghost" target="_blank" href="/blog/' . e($post['slug']) . '/">' . ($live ? 'View' : 'Preview') . '</a>' : '') . '</div></form>'
   . '<script>(function(){var ed=document.getElementById("ed"),f=document.getElementById("ef");
document.querySelectorAll(".tb [data-c]").forEach(function(b){b.addEventListener("click",function(){ed.focus();document.execCommand(b.dataset.c,false,b.dataset.v?"<"+b.dataset.v+">":null);});});
document.getElementById("lnk").onclick=function(){var u=prompt("Link address (https://… or /products/…)");if(u){ed.focus();document.execCommand("createLink",false,u);}};
var fi=document.getElementById("imgf");document.getElementById("img").onclick=function(){fi.click();};
fi.onchange=function(){if(!fi.files[0])return;var fd=new FormData();fd.append("image",fi.files[0]);fd.append("csrf",' . json_encode(csrf()) . ');
fetch("?p=upload",{method:"POST",body:fd,credentials:"same-origin"}).then(function(r){return r.json()}).then(function(j){if(j.url){ed.focus();var alt=prompt("Describe the image (for Google and accessibility)")||"";document.execCommand("insertHTML",false,"<p><img src=\""+j.url+"\" alt=\""+alt.replace(/"/g,"")+"\"></p>");}else alert(j.error||"Upload failed");});fi.value="";};
ed.addEventListener("paste",function(e){e.preventDefault();var t=(e.clipboardData||window.clipboardData).getData("text/plain");document.execCommand("insertText",false,t);});
f.addEventListener("submit",function(){document.getElementById("content").value=ed.innerHTML;});})();</script>';
  admin_shell($isNew ? 'New article' : 'Edit article', $html);
}

function ajax_upload(): void {
  header('Content-Type: application/json');
  if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) { echo json_encode(['error' => 'Session expired']); exit; }
  $u = isset($_FILES['image']) ? save_image_upload($_FILES['image']) : '';
  echo json_encode($u ? ['url' => $u] : ['error' => 'Use a JPG, PNG or WebP image under 8 MB.']); exit;
}

function page_settings(string $msg): void {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $in = array_map(fn($v) => is_string($v) ? trim($v) : '', $_POST);
    $s = settings();
    $new = ['notify_emails' => implode(', ', array_filter(array_map('trim', explode(',', $in['notify_emails'] ?? '')), fn($x) => filter_var($x, FILTER_VALIDATE_EMAIL))),
      'from_email' => filter_var($in['from_email'] ?? '', FILTER_VALIDATE_EMAIL) ? $in['from_email'] : $s['from_email'],
      'from_name' => mb_substr($in['from_name'] ?? '', 0, 80) ?: $s['from_name'],
      'site_url' => preg_match('~^https?://[a-z0-9.-]+$~i', rtrim($in['site_url'] ?? '', '/')) ? rtrim($in['site_url'], '/') : $s['site_url'],
      'smtp_host' => $in['smtp_host'] ?? '', 'smtp_port' => (string)(int)($in['smtp_port'] ?? 465), 'smtp_secure' => in_array($in['smtp_secure'] ?? '', ['ssl', 'tls', 'none'], true) ? $in['smtp_secure'] : 'ssl',
      'smtp_user' => $in['smtp_user'] ?? '',
      'wa_provider' => in_array($in['wa_provider'] ?? '', ['callmebot', 'cloudapi', 'off'], true) ? $in['wa_provider'] : 'callmebot',
      'wa_number' => preg_replace('/\D/', '', $in['wa_number'] ?? ''), 'wa_callmebot_key' => $in['wa_callmebot_key'] ?? '',
      'wa_cloud_phone_id' => $in['wa_cloud_phone_id'] ?? '', 'wa_cloud_template' => $in['wa_cloud_template'] ?? '', 'wa_cloud_lang' => $in['wa_cloud_lang'] ?? 'en',
      'public_whatsapp' => preg_replace('/\D/', '', $in['public_whatsapp'] ?? '')];
    if (($in['smtp_pass'] ?? '') !== '') $new['smtp_pass'] = $in['smtp_pass'];
    if (($in['wa_cloud_token'] ?? '') !== '') $new['wa_cloud_token'] = $in['wa_cloud_token'];
    if ($new['notify_emails'] === '') { flash('Enter at least one valid email for enquiries.', 'err'); go('?p=settings'); }
    save_settings($new);
    if (isset($_POST['test_email'])) { $r = send_mail(explode(',', $new['notify_emails']), 'Test email from JASSCO website', "This is a test email from your website admin.\nIf you received it, enquiry emails are working."); flash($r['ok'] ? 'Settings saved. Test email sent — check the inbox (and spam folder).' : 'Settings saved, but the test email failed: ' . $r['error'], $r['ok'] ? 'ok' : 'err'); }
    elseif (isset($_POST['test_wa'])) { $r = whatsapp_alert("✅ Test alert from the JASSCO website admin. WhatsApp enquiry alerts are working.", ['Test', 'India', 'Test message', 'TEST']); flash($r['ok'] ? 'Settings saved. Test WhatsApp sent — check the phone.' : 'Settings saved, but WhatsApp failed: ' . $r['error'], $r['ok'] ? 'ok' : 'err'); }
    else flash('Settings saved.');
    go('?p=settings');
  }
  $s = settings();
  $sel = fn($n, $v, $o) => '<option value="' . $v . '"' . ($s[$n] === $v ? ' selected' : '') . '>' . $o . '</option>';
  $html = $msg . '<h1>Settings</h1><form method="post" class="form" style="max-width:760px">' . csrf_field()
   . '<fieldset><legend>Enquiry emails</legend><label>Send enquiries to (separate several emails with commas)<input name="notify_emails" value="' . e($s['notify_emails']) . '" required></label>'
   . '<label>Sender address (should be an email on your own domain, e.g. no-reply@jasscosteel.com)<input name="from_email" value="' . e($s['from_email']) . '"></label><label>Sender name<input name="from_name" value="' . e($s['from_name']) . '"></label>'
   . '<details' . ($s['smtp_host'] ? ' open' : '') . '><summary>Advanced: send through SMTP (use only if emails don’t arrive)</summary><p class="small muted">Leave the server empty to use the hosting’s built-in mail. For Gmail: server smtp.gmail.com, port 465, SSL, your Gmail address and a Google “App password”.</p>'
   . '<div class="grid2"><label>SMTP server<input name="smtp_host" value="' . e($s['smtp_host']) . '" placeholder="e.g. smtpout.secureserver.net"></label><label>Port<input name="smtp_port" value="' . e($s['smtp_port']) . '"></label></div>'
   . '<div class="grid2"><label>Security<select name="smtp_secure">' . $sel('smtp_secure', 'ssl', 'SSL (port 465)') . $sel('smtp_secure', 'tls', 'STARTTLS (port 587)') . $sel('smtp_secure', 'none', 'None') . '</select></label><label>Username<input name="smtp_user" value="' . e($s['smtp_user']) . '" autocomplete="off"></label></div>'
   . '<label>Password ' . ($s['smtp_pass'] ? '(saved — leave empty to keep)' : '') . '<input type="password" name="smtp_pass" autocomplete="new-password"></label></details>'
   . '<button class="btn ghost" name="test_email" value="1">Save and send a test email</button></fieldset>'
   . '<fieldset id="wa"><legend>WhatsApp alerts</legend><label>Alert method<select name="wa_provider">' . $sel('wa_provider', 'callmebot', 'CallMeBot – free, quick setup') . $sel('wa_provider', 'cloudapi', 'WhatsApp Business Cloud API (Meta, official)') . $sel('wa_provider', 'off', 'Off') . '</select></label>'
   . '<label>Send alerts to WhatsApp number (with country code, e.g. 919971315142)<input name="wa_number" value="' . e($s['wa_number']) . '"></label>'
   . '<div class="box"><strong>CallMeBot setup (one time, 2 minutes)</strong><ol><li>On the phone that should receive alerts, save the CallMeBot number shown at <a href="https://www.callmebot.com/blog/free-api-whatsapp-messages/" target="_blank" rel="noopener">callmebot.com</a> as a contact.</li><li>Send it this WhatsApp message: <code>I allow callmebot to send me messages</code></li><li>You will receive a reply with your <b>APIKEY</b>. Paste it below, save, and press “Send a test WhatsApp”.</li></ol><label>CallMeBot API key<input name="wa_callmebot_key" value="' . e($s['wa_callmebot_key']) . '" autocomplete="off"></label></div>'
   . '<details' . ($s['wa_provider'] === 'cloudapi' ? ' open' : '') . '><summary>WhatsApp Business Cloud API settings (official, for higher volume)</summary><p class="small muted">Requires a Meta Business account. Business-initiated messages must use an approved message template whose body has 4 variables: {{1}} customer, {{2}} country, {{3}} product, {{4}} reference.</p>'
   . '<label>Access token ' . ($s['wa_cloud_token'] ? '(saved — leave empty to keep)' : '') . '<input type="password" name="wa_cloud_token" autocomplete="off"></label><div class="grid2"><label>Phone number ID<input name="wa_cloud_phone_id" value="' . e($s['wa_cloud_phone_id']) . '"></label><label>Template name<input name="wa_cloud_template" value="' . e($s['wa_cloud_template']) . '"></label></div><label>Template language code<input name="wa_cloud_lang" value="' . e($s['wa_cloud_lang']) . '"></label></details>'
   . '<button class="btn ghost" name="test_wa" value="1">Save and send a test WhatsApp</button></fieldset>'
   . '<fieldset><legend>Website</legend><label>WhatsApp chat button number shown to visitors<input name="public_whatsapp" value="' . e($s['public_whatsapp']) . '"></label><label>Website address<input name="site_url" value="' . e($s['site_url']) . '"></label></fieldset>'
   . '<button class="btn">Save settings</button></form>';
  admin_shell('Settings', $html);
}

function page_password(string $msg): void {
  global $authFile, $auth; $err = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!password_verify((string)$_POST['old'], $auth['hash'])) $err = 'Current password is incorrect.';
    elseif (strlen((string)$_POST['new']) < 10) $err = 'New password must be at least 10 characters.';
    elseif ($_POST['new'] !== $_POST['new2']) $err = 'New passwords do not match.';
    else { $auth['hash'] = password_hash((string)$_POST['new'], PASSWORD_DEFAULT); json_write($authFile, $auth); flash('Password changed.'); go('?p=password'); }
  }
  admin_shell('Change password', $msg . ($err ? '<div class="note err">' . e($err) . '</div>' : '') . '<h1>Change password</h1><form method="post" class="form" style="max-width:420px">' . csrf_field() . '<label>Current password<input type="password" name="old" required></label><label>New password<input type="password" name="new" required></label><label>Repeat new password<input type="password" name="new2" required></label><button class="btn">Change password</button></form>');
}

/* ================= SHELL ================= */
function admin_shell(string $title, string $content, bool $nav = true): void {
  $p = (string)($_GET['p'] ?? 'dashboard');
  $a = fn($k) => $p === $k || ($k === 'posts' && $p === 'edit') || ($k === 'enquiries' && $p === 'enquiry') ? ' class="on"' : '';
  $menu = $nav ? '<aside class="side"><a href="/admin/" class="logo"><img src="/assets/img/jassco-logo.svg" alt="JASSCO" height="34"></a><nav><a href="/admin/"' . $a('dashboard') . '>Dashboard</a><a href="?p=enquiries"' . $a('enquiries') . '>Enquiries</a><a href="?p=posts"' . $a('posts') . '>Blog articles</a><a href="?p=settings"' . $a('settings') . '>Settings</a><a href="?p=password"' . $a('password') . '>Change password</a><a href="/" target="_blank">View website ↗</a><a href="?p=logout">Log out</a></nav></aside>' : '';
  echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . e($title) . ' · JASSCO admin</title><link rel="icon" href="/favicon-32.png">'
   . '<style>' . ADMIN_CSS . '</style></head><body class="' . ($nav ? 'app' : 'bare') . '">' . $menu . '<main>' . $content . '</main></body></html>';
}
