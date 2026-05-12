-- =============================================
-- DATABASE: pinshare_db
-- Jalankan file ini di phpMyAdmin
-- Menu: Import > pilih file ini > klik Go
-- =============================================

CREATE DATABASE IF NOT EXISTS pinshare_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pinshare_db;

-- -----------------------------------------------
-- TABEL USERS
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  email         VARCHAR(100) NOT NULL UNIQUE,
  password      VARCHAR(255) NOT NULL,          -- disimpan sebagai hash bcrypt
  full_name     VARCHAR(100),
  bio           TEXT,
  avatar        VARCHAR(255),                   -- nama file avatar di /uploads/avatars/
  website       VARCHAR(255),
  is_verified   TINYINT(1)   DEFAULT 0,
  created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------
-- TABEL CATEGORIES
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(50) NOT NULL UNIQUE,
  icon  VARCHAR(10) DEFAULT '📌'
) ENGINE=InnoDB;

-- Data kategori awal
INSERT INTO categories (name, icon) VALUES
  ('Desain',      '🎨'),
  ('Teknologi',   '💻'),
  ('Fotografi',   '📷'),
  ('Arsitektur',  '🏠'),
  ('Makanan',     '🍳'),
  ('Fashion',     '👗'),
  ('Seni',        '🖼️'),
  ('Perjalanan',  '✈️'),
  ('Olahraga',    '⚽'),
  ('Musik',       '🎵'),
  ('DIY',         '🔨'),
  ('Pendidikan',  '📚');

-- -----------------------------------------------
-- TABEL PINS
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS pins (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT          NOT NULL,
  category_id  INT,
  title        VARCHAR(200) NOT NULL,
  description  TEXT,
  image        VARCHAR(255) NOT NULL,           -- nama file gambar di /uploads/
  source_url   VARCHAR(500),                    -- link sumber asli (opsional)
  views        INT          DEFAULT 0,
  created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------
-- TABEL BOARDS (koleksi pin milik user)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS boards (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT          NOT NULL,
  name        VARCHAR(100) NOT NULL,
  description TEXT,
  is_private  TINYINT(1)   DEFAULT 0,
  cover_image VARCHAR(255),
  created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------
-- TABEL SAVED PINS (simpan pin ke board)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS saved_pins (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  pin_id     INT NOT NULL,
  board_id   INT,
  saved_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_save (user_id, pin_id, board_id),
  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
  FOREIGN KEY (pin_id)   REFERENCES pins(id)   ON DELETE CASCADE,
  FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------
-- TABEL LIKES
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS likes (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  pin_id     INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_like (user_id, pin_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (pin_id)  REFERENCES pins(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------
-- TABEL COMMENTS
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS comments (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT  NOT NULL,
  pin_id     INT  NOT NULL,
  parent_id  INT  DEFAULT NULL,               -- untuk reply komentar
  content    TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)   REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (pin_id)    REFERENCES pins(id)     ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------
-- TABEL FOLLOWS (mengikuti user lain)
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS follows (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  follower_id INT NOT NULL,
  following_id INT NOT NULL,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_follow (follower_id, following_id),
  FOREIGN KEY (follower_id)  REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------
-- TABEL NOTIFICATIONS
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT          NOT NULL,          -- penerima notifikasi
  from_user_id INT,                           -- siapa yang memicu notifikasi
  type        ENUM('like','comment','follow','save') NOT NULL,
  pin_id      INT,
  message     TEXT,
  is_read     TINYINT(1)   DEFAULT 0,
  created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)      REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (pin_id)       REFERENCES pins(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------
-- INDEX untuk performa query
-- -----------------------------------------------
CREATE INDEX idx_pins_user     ON pins(user_id);
CREATE INDEX idx_pins_category ON pins(category_id);
CREATE INDEX idx_pins_created  ON pins(created_at DESC);
CREATE INDEX idx_likes_pin     ON likes(pin_id);
CREATE INDEX idx_comments_pin  ON comments(pin_id);
CREATE INDEX idx_notif_user    ON notifications(user_id, is_read);
