<?php
declare(strict_types=1);

/**
 * PDO + helpers
 */

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  $cfg = require __DIR__ . '/config.php';
  $db  = $cfg['db'];

  $dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $db['host'],
    (int)($db['port'] ?? 3306),
    $db['name'],
    $db['charset'] ?? 'utf8mb4'
  );

  $pdo = new PDO($dsn, $db['user'], $db['pass'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ]);

  return $pdo;
}

function db_all(string $sql, array $params = []): array {
  $st = db()->prepare($sql);
  $st->execute($params);
  return $st->fetchAll();
}

function db_one(string $sql, array $params = []): ?array {
  $st = db()->prepare($sql);
  $st->execute($params);
  $row = $st->fetch();
  return $row === false ? null : $row;
}

function db_exec(string $sql, array $params = []): int {
  $st = db()->prepare($sql);
  $st->execute($params);
  return $st->rowCount();
}

/**
 * Schema helpers
 */

function db_has_table(string $table): bool {
  $row = db_one(
    'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
    [$table]
  );
  return ((int)($row['c'] ?? 0)) > 0;
}

function db_has_column(string $table, string $column): bool {
  $row = db_one(
    'SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
    [$table, $column]
  );
  return ((int)($row['c'] ?? 0)) > 0;
}

/**
 * Lista as colunas existentes numa tabela (pela ordem física).
 * Serve para páginas que precisam de tolerar pequenas variações do esquema.
 */
function db_cols(string $table): array {
  $rows = db_all(
    'SELECT COLUMN_NAME AS name
       FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
      ORDER BY ORDINAL_POSITION',
    [$table]
  );
  return array_values(array_map(static fn($r) => (string)$r['name'], $rows));
}

function db_ensure_column(string $table, string $column, string $ddl): void {
  if (db_has_column($table, $column)) return;
  db_exec("ALTER TABLE `{$table}` ADD COLUMN {$ddl}");
}

/**
 * Consolidação mínima de esquema (V053)
 * - BD em inglês, UI em português.
 * - Mantém compatibilidade com colunas antigas (username/full_name).
 */
function ensure_schema(): void {
  // Users (mínimo para autenticação + ecrã de Utilizadores)
  if (!db_has_table('users')) {
    db_exec(
      'CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        email VARCHAR(200) NULL,
        phone VARCHAR(50) NULL,
        title VARCHAR(200) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        login VARCHAR(100) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        website VARCHAR(255) NULL,
        photo_path VARCHAR(255) NULL,
        role VARCHAR(20) NOT NULL DEFAULT \'admin\',
        created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    // Admin inicial (login: admin / pass: admin)
    $hash = password_hash('admin', PASSWORD_DEFAULT);
    db_exec('INSERT INTO users (name, login, password_hash, role, is_active) VALUES (\'Administrador\',\'admin\',?,\'admin\',1)', [$hash]);
  }

  // Compatibilidade: se existir schema antigo, criar colunas novas e migrar dados
  if (db_has_table('users')) {
    db_ensure_column('users', 'name', 'name VARCHAR(200) NULL');
    db_ensure_column('users', 'email', 'email VARCHAR(200) NULL');
    db_ensure_column('users', 'phone', 'phone VARCHAR(50) NULL');
    db_ensure_column('users', 'title', 'title VARCHAR(200) NULL');
    db_ensure_column('users', 'is_active', 'is_active TINYINT(1) NOT NULL DEFAULT 1');
    db_ensure_column('users', 'login', 'login VARCHAR(100) NULL');
    db_ensure_column('users', 'password_hash', 'password_hash VARCHAR(255) NULL');
    db_ensure_column('users', 'website', 'website VARCHAR(255) NULL');
    db_ensure_column('users', 'photo_path', 'photo_path VARCHAR(255) NULL');
    db_ensure_column('users', 'role', 'role VARCHAR(20) NOT NULL DEFAULT \'admin\'');
    db_ensure_column('users', 'created_at_utc', 'created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

    // Migração simples, se vier de versões antigas
    if (db_has_column('users', 'username') && db_has_column('users', 'login')) {
      db_exec('UPDATE users SET login = COALESCE(login, username)');
    }
    if (db_has_column('users', 'full_name') && db_has_column('users', 'name')) {
      db_exec('UPDATE users SET name = COALESCE(name, full_name)');
    }

    // Garantir obrigatórios
    db_exec('UPDATE users SET login = COALESCE(login, CONCAT(\'user\', id)) WHERE login IS NULL OR login = \'\'');
    db_exec('UPDATE users SET name = COALESCE(NULLIF(name,\'\'), login) WHERE name IS NULL OR name = \'\'');
    db_exec('UPDATE users SET password_hash = COALESCE(password_hash, \'\')');
  }

  // Tabela de associação: Consultor -> Clientes
  if (!db_has_table('user_clients')) {
    db_exec(
      'CREATE TABLE user_clients (
        user_id INT NOT NULL,
        client_id INT NOT NULL,
        PRIMARY KEY (user_id, client_id),
        INDEX ix_uc_client (client_id)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
  }

  // Tabela de associação: Formador -> Projetos
  if (!db_has_table('user_projects')) {
    db_exec(
      'CREATE TABLE user_projects (
        user_id INT NOT NULL,
        project_id INT NOT NULL,
        PRIMARY KEY (user_id, project_id),
        INDEX ix_up_project (project_id)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
  }
}
