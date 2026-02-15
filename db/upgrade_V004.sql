-- V004: foto do utilizador
ALTER TABLE users
  ADD COLUMN photo_path VARCHAR(255) NULL AFTER password_hash,
  ADD COLUMN photo_zoom DECIMAL(6,3) NOT NULL DEFAULT 1.000 AFTER photo_path,
  ADD COLUMN photo_x INT NOT NULL DEFAULT 0 AFTER photo_zoom,
  ADD COLUMN photo_y INT NOT NULL DEFAULT 0 AFTER photo_x;
