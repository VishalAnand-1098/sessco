<?php
/* Email sending: SMTP (if configured in Admin > Settings) or PHP mail() on the hosting server. */
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

/**
 * @param string[] $to
 * @param array<int,array{path:string,name:string}> $files
 * @return array{ok:bool,error:string}
 */
function send_mail(array $to, string $subject, string $text, string $replyTo = '', string $replyName = '', array $files = []): array {
  $s = settings();
  $to = array_values(array_filter(array_map('trim', $to), fn($x) => (bool)filter_var($x, FILTER_VALIDATE_EMAIL)));
  if (!$to) return ['ok' => false, 'error' => 'No valid recipient email set'];
  $from = filter_var($s['from_email'], FILTER_VALIDATE_EMAIL) ? $s['from_email'] : 'no-reply@' . preg_replace('/^www\./', '', (string)parse_url(site_url(), PHP_URL_HOST));
  $enc = fn(string $t) => '=?UTF-8?B?' . base64_encode($t) . '?=';
  $boundary = 'b' . bin2hex(random_bytes(12));
  $h = [];
  $h[] = 'Date: ' . date('r');
  $h[] = 'From: ' . $enc($s['from_name']) . ' <' . $from . '>';
  if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) $h[] = 'Reply-To: ' . $enc($replyName ?: $replyTo) . ' <' . $replyTo . '>';
  $h[] = 'MIME-Version: 1.0';
  $h[] = 'Message-ID: <' . bin2hex(random_bytes(10)) . '@' . substr(strrchr($from, '@'), 1) . '>';
  if ($files) {
    $h[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
    $body = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text));
    foreach ($files as $f) {
      if (!is_file($f['path'])) continue;
      $n = preg_replace('/[^A-Za-z0-9._-]/', '_', $f['name']);
      $body .= "--$boundary\r\nContent-Type: application/octet-stream; name=\"$n\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"$n\"\r\n\r\n" . chunk_split(base64_encode((string)file_get_contents($f['path'])));
    }
    $body .= "--$boundary--\r\n";
  } else {
    $h[] = 'Content-Type: text/plain; charset=UTF-8';
    $h[] = 'Content-Transfer-Encoding: base64';
    $body = chunk_split(base64_encode($text));
  }
  if (trim($s['smtp_host']) !== '') return smtp_send($s, $from, $to, $enc($subject), $h, $body);
  $ok = @mail(implode(', ', $to), $enc($subject), $body, implode("\r\n", $h), '-f' . $from);
  return ['ok' => $ok, 'error' => $ok ? '' : 'PHP mail() failed on this server'];
}

function smtp_send(array $s, string $from, array $to, string $subjectEnc, array $headers, string $body): array {
  $host = trim($s['smtp_host']); $port = (int)($s['smtp_port'] ?: 465); $secure = $s['smtp_secure'];
  $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
  $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
  $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
  if (!$fp) return ['ok' => false, 'error' => "SMTP connect failed: $errstr"];
  stream_set_timeout($fp, 20);
  $read = function () use ($fp): string { $r = ''; while (($l = fgets($fp, 515)) !== false) { $r .= $l; if (strlen($l) < 4 || $l[3] === ' ') break; } return $r; };
  $cmd = function (string $c, array $okCodes) use ($fp, $read): string { if ($c !== '') fwrite($fp, $c . "\r\n"); $r = $read(); if (!in_array((int)substr($r, 0, 3), $okCodes, true)) throw new RuntimeException(trim($r) ?: 'no response'); return $r; };
  try {
    $cmd('', [220]);
    $ehlo = 'EHLO ' . (gethostname() ?: 'localhost');
    $cmd($ehlo, [250]);
    if ($secure === 'tls') {
      $cmd('STARTTLS', [220]);
      if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('STARTTLS failed');
      $cmd($ehlo, [250]);
    }
    if ($s['smtp_user'] !== '') {
      $cmd('AUTH LOGIN', [334]); $cmd(base64_encode($s['smtp_user']), [334]); $cmd(base64_encode($s['smtp_pass']), [235]);
    }
    $cmd("MAIL FROM:<$from>", [250]);
    foreach ($to as $t) $cmd("RCPT TO:<$t>", [250, 251]);
    $cmd('DATA', [354]);
    $msg = implode("\r\n", array_merge(['To: ' . implode(', ', $to), 'Subject: ' . $subjectEnc], $headers)) . "\r\n\r\n" . $body;
    $msg = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], ["\n", "\n"], $msg)) ?? '';
    fwrite($fp, str_replace("\n", "\r\n", $msg) . "\r\n.\r\n");
    $cmd('', [250]);
    $cmd('QUIT', [221]);
    fclose($fp);
    return ['ok' => true, 'error' => ''];
  } catch (Throwable $ex) {
    @fclose($fp);
    return ['ok' => false, 'error' => 'SMTP: ' . $ex->getMessage()];
  }
}
