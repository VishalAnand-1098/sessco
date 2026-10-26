<?php
/* WhatsApp enquiry alerts to JASSCO's number.
   Providers (choose in Admin > Settings):
   - callmebot : free alert service. Activate once from the receiving phone (see Admin > Settings for steps).
   - cloudapi  : official Meta WhatsApp Business Cloud API (needs a Meta business account and an approved template).
*/
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function http_req(string $url, ?string $postJson = null, array $headers = []): array {
  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12, CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_FOLLOWLOCATION => true]);
    if ($postJson !== null) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $postJson); $headers[] = 'Content-Type: application/json'; }
    if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $body = (string)curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    return [$code, $body, $err];
  }
  $opts = ['http' => ['timeout' => 12, 'ignore_errors' => true, 'method' => $postJson !== null ? 'POST' : 'GET', 'header' => implode("\r\n", $postJson !== null ? array_merge($headers, ['Content-Type: application/json']) : $headers), 'content' => $postJson ?? '']];
  $body = @file_get_contents($url, false, stream_context_create($opts));
  $code = 0; if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $code = (int)$m[1];
  return [$code, (string)$body, $body === false ? 'request failed' : ''];
}

/** @return array{ok:bool,error:string} */
function whatsapp_alert(string $text, array $templateParams = []): array {
  $s = settings();
  $to = preg_replace('/\D+/', '', (string)$s['wa_number']);
  if ($s['wa_provider'] === 'off' || $to === '') return ['ok' => false, 'error' => 'WhatsApp alerts are switched off'];
  if ($s['wa_provider'] === 'callmebot') {
    if (trim($s['wa_callmebot_key']) === '') return ['ok' => false, 'error' => 'CallMeBot API key not set (Admin > Settings)'];
    $url = 'https://api.callmebot.com/whatsapp.php?phone=+' . $to . '&text=' . rawurlencode(mb_substr($text, 0, 1500)) . '&apikey=' . rawurlencode(trim($s['wa_callmebot_key']));
    [$code, $body, $err] = http_req($url);
    $ok = $code >= 200 && $code < 300 && stripos($body, 'error') === false;
    return ['ok' => $ok, 'error' => $ok ? '' : trim("HTTP $code $err " . mb_substr(strip_tags($body), 0, 200))];
  }
  if ($s['wa_provider'] === 'cloudapi') {
    if (!$s['wa_cloud_token'] || !$s['wa_cloud_phone_id']) return ['ok' => false, 'error' => 'Cloud API token / phone number ID not set'];
    if ($s['wa_cloud_template'] !== '') {
      $params = array_map(fn($p) => ['type' => 'text', 'text' => mb_substr((string)$p, 0, 900)], $templateParams);
      $payload = ['messaging_product' => 'whatsapp', 'to' => $to, 'type' => 'template', 'template' => ['name' => $s['wa_cloud_template'], 'language' => ['code' => $s['wa_cloud_lang'] ?: 'en'], 'components' => $params ? [['type' => 'body', 'parameters' => $params]] : []]];
    } else {
      $payload = ['messaging_product' => 'whatsapp', 'to' => $to, 'type' => 'text', 'text' => ['body' => mb_substr($text, 0, 4000)]];
    }
    [$code, $body, $err] = http_req('https://graph.facebook.com/v20.0/' . rawurlencode($s['wa_cloud_phone_id']) . '/messages', json_encode($payload), ['Authorization: Bearer ' . $s['wa_cloud_token']]);
    $ok = $code >= 200 && $code < 300;
    return ['ok' => $ok, 'error' => $ok ? '' : trim("HTTP $code $err " . mb_substr($body, 0, 300))];
  }
  return ['ok' => false, 'error' => 'Unknown provider'];
}
