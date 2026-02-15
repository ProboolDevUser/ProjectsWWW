-- ProBool Project Hub – Schema (V053)
-- BD em inglês; UI em português.

CREATE TABLE IF NOT EXISTS clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  email VARCHAR(200) NULL,
  phone VARCHAR(50) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  title VARCHAR(250) NOT NULL,
  code VARCHAR(50) NULL,
  start_date_utc DATETIME NULL,
  due_date_utc DATETIME NULL,
  color_tag VARCHAR(30) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_projects_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
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
  role VARCHAR(20) NOT NULL DEFAULT 'admin',
  created_at_utc DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_users_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_clients (
  user_id INT NOT NULL,
  client_id INT NOT NULL,
  PRIMARY KEY (user_id, client_id),
  INDEX ix_uc_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_projects (
  user_id INT NOT NULL,
  project_id INT NOT NULL,
  PRIMARY KEY (user_id, project_id),
  INDEX ix_up_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
