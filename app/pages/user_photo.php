<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$userId = (int)($_SESSION['user']['id'] ?? 0);
if ($userId <= 0) { http_response_code(404); exit; }

$st = db()->prepare("SELECT photo_path FROM users WHERE id=?");
$st->execute([$userId]);
$u = $st->fetch();

$path = null;
if ($u && !empty($u['photo_path'])) {
  $candidate = __DIR__ . '/../../public/' . ltrim($u['photo_path'], '/');
  if (is_file($candidate)) $path = $candidate;
}

if (!$path) {
  $path = __DIR__ . '/../../public/assets/img/avatar.svg';
  header('Content-Type: image/svg+xml; charset=utf-8');
  readfile($path);
  exit;
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$map = ['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','webp'=>'image/webp','gif'=>'image/gif'];
header('Content-Type: ' . ($map[$ext] ?? 'application/octet-stream'));
readfile($path);
