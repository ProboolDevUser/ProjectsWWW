<?php
declare(strict_types=1);

/**
 * Autenticação simples (MySQL/PDO) para o Project Hub.
 * - users: id, username, full_name, password_hash, is_active
 * Nota: nesta fase, o utilizador 'admin' é tratado como Administrador.
 */

function auth_login(string $username, string $password): bool {
  $username = trim($username);
  if ($username === '' || $password === '') return false;

  // Compatibilidade: a BD pode ter login='login' (novo) ou username (antigo)
  $loginCol = (function_exists('db_has_column') && db_has_column('users', 'login')) ? 'login' : 'username';
  $nameCol  = (function_exists('db_has_column') && db_has_column('users', 'name'))  ? 'name'  : 'full_name';

  $u = db_one(
    "SELECT id, {$loginCol} AS login, {$nameCol} AS name, password_hash, is_active, role FROM users WHERE {$loginCol} = ? LIMIT 1",
    [$username]
  );

  if (!$u) return false;
  if ((int)($u['is_active'] ?? 0) !== 1) return false;

  $hash = (string)($u['password_hash'] ?? '');
  if ($hash === '' || !password_verify($password, $hash)) return false;

  // Sessão
  $_SESSION['user_id'] = (int)$u['id'];
  return true;
}

function auth_logout(): void {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
      $params['path'] ?? '/',
      $params['domain'] ?? '',
      (bool)($params['secure'] ?? false),
      (bool)($params['httponly'] ?? true)
    );
  }
  session_destroy();
}

function current_user(): ?array {
  $id = (int)($_SESSION['user_id'] ?? 0);
  if ($id <= 0) return null;

  $loginCol = (function_exists('db_has_column') && db_has_column('users', 'login')) ? 'login' : 'username';
  $nameCol  = (function_exists('db_has_column') && db_has_column('users', 'name'))  ? 'name'  : 'full_name';

  $u = db_one(
    "SELECT id, {$loginCol} AS login, {$nameCol} AS name, is_active, role FROM users WHERE id = ? LIMIT 1",
    [$id]
  );
  if (!$u) return null;
  if ((int)($u['is_active'] ?? 0) !== 1) return null;

  // Papel lógico (normalizado)
  $r = role_norm($u['role'] ?? null);
  if ($r === 'user' && strtolower((string)($u['login'] ?? '')) === 'admin') $r = 'admin';
  $u['role'] = $r;
  return $u;
}

/**
 * Exige um (ou vários) perfis.
 *
 * Exemplos:
 *   require_role('admin');
 *   require_role(['admin','consultant']);
 */
function require_role(string|array $roles): void {
  require_login();

  $u = current_user();
  $r = strtolower((string)($u['role'] ?? 'user'));

  $allowed = [];
  if (is_array($roles)) {
    foreach ($roles as $x) {
      if ($x === null) continue;
      $allowed[] = strtolower((string)$x);
    }
  } else {
    $allowed[] = strtolower((string)$roles);
  }

  if (!in_array($r, $allowed, true)) {
    http_response_code(403);
    echo "Acesso negado.";
    exit;
  }
}
