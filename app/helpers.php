<?php
declare(strict_types=1);
require_once __DIR__ . '../helpers/context.php';


function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function role_norm(?string $role): string {
  $r = strtolower(trim((string)($role ?? '')));
  // Aceitar rótulos PT e EN
  if (in_array($r, ['administrador', 'admin'], true)) return 'admin';
  if (in_array($r, ['consultor', 'consultant'], true)) return 'consultant';
  if (in_array($r, ['formador', 'trainer'], true)) return 'trainer';
  if ($r === '') return 'user';
  return $r;
}

function current_user_role(): string {
  $u = current_user();
  if (!$u) return 'user';
  return role_norm($u['role'] ?? null);
}

function redirect(string $to): never {
  header("Location: {$to}");
  exit;
}

function nowUtc(): DateTimeImmutable {
  return new DateTimeImmutable('now', new DateTimeZone('UTC'));
}

function toLisbon(?string $utc): string {
  if (!$utc) return '-';
  $dt = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
  return $dt->setTimezone(new DateTimeZone('Europe/Lisbon'))->format('Y-m-d H:i');
}

function require_login(): void {
  // A autenticação é determinada pela sessão (user_id) e pela existência do utilizador na BD.
  // Isto evita loops de redirect quando o array do utilizador não é guardado na sessão.
  if (!function_exists('current_user') || !current_user()) {
    redirect('index.php?p=login');
  }
}

function csrf_token(): string {
  if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['_csrf'];
}

function csrf_field(): string {
  $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
  return '<input type="hidden" name="_csrf" value="'.$t.'">';
}

function csrf_check(): void {
  $t = $_POST['_csrf'] ?? '';
  if (empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], (string)$t)) {
    http_response_code(400);
    echo "Pedido inválido (CSRF).";
    exit;
  }
}

// NOTA: require_role() vive em app/auth.php para evitar duplicações.
