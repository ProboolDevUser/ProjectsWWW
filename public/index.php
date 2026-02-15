<?php
declare(strict_types=1);

session_start();

$cfg = require __DIR__ . '/../app/config.php';
date_default_timezone_set($cfg['app']['tz']);

require_once __DIR__ . '/../app/db.php';
// Garantir que as tabelas/colunas mínimas existem (evita erros tipo "table doesn't exist")
if (function_exists('ensure_schema')) {
  ensure_schema();
}
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/helpers.php';

$p = $_GET['p'] ?? 'dashboard';

/**
 * Páginas que NÃO devem renderizar o layout (precisam de headers/redirect sem HTML prévio).
 */
$noLayout = ['login','logout','user_photo','context_set'];

if ($p === 'logout') {
  require __DIR__ . '/../app/pages/logout.php';
  exit;
}

if ($p === 'user_photo') {
  require_login();
  require __DIR__ . '/../app/pages/user_photo.php';
  exit;
}

if ($p === 'context_set') {
  require_login();
  require __DIR__ . '/../app/pages/context_set.php';
  exit;
}

if ($p === 'login') {
  require __DIR__ . '/../app/pages/login.php';
  exit;
}

$file = __DIR__ . '/../app/pages/' . basename($p) . '.php';

if (!is_file($file)) {
  http_response_code(404);
  echo "Página não encontrada.";
  exit;
}

require_login();

require __DIR__ . '/../app/layout/header.php';
require $file;
require __DIR__ . '/../app/layout/footer.php';
