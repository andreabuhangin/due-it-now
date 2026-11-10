<?php
// Local-only secrets (this file is NOT committed to GitHub)
$local = __DIR__ . '/google.local.php';
$cfg = file_exists($local) ? require $local : [];

$appUrl = getenv('APP_URL');

return [
  "client_id"     => getenv('GOOGLE_CLIENT_ID')     ?: ($cfg['client_id'] ?? ''),
  "client_secret" => getenv('GOOGLE_CLIENT_SECRET') ?: ($cfg['client_secret'] ?? ''),
  "redirect_uri"  => $appUrl
      ? rtrim($appUrl, '/') . '/api/google_callback.php'
      : "http://localhost/due_it_now/api/google_callback.php",
  "timezone"      => "Asia/Manila",
];
