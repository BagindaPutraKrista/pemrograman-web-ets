-- Veteran Marketplace Database Schema
-- Run this file to set up the database

CREATE DATABASE IF NOT EXISTS veteran_marketplace
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE veteran_marketplace;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    bio             TEXT,
    contact         VARCHAR(100),
    profile_photo   VARCHAR(255),
    notif_chat      TINYINT(1) NOT NULL DEFAULT 1,
    notif_transaction TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    name    VARCHAR(100) NOT NULL,
    slug    VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: items
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    seller_id   INT NOT NULL,
    category_id INT NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    price       DECIMAL(15,2) NOT NULL,
    location    VARCHAR(150) NOT NULL,
    status      ENUM('Tersedia','Dipesan','Terjual') NOT NULL DEFAULT 'Tersedia',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_items_seller   FOREIGN KEY (seller_id)   REFERENCES users(id)       ON DELETE CASCADE,
    CONSTRAINT fk_items_category FOREIGN KEY (category_id) REFERENCES categories(id)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: item_photos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS item_photos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    item_id     INT NOT NULL,
    photo_path  VARCHAR(255) NOT NULL,
    is_primary  TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_item_photos_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: wishlists
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS wishlists (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    item_id     INT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wishlist (user_id, item_id),
    CONSTRAINT fk_wishlists_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_wishlists_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: chat_rooms
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS chat_rooms (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id    INT NOT NULL,
    seller_id   INT NOT NULL,
    item_id     INT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_chat_room (buyer_id, seller_id, item_id),
    CONSTRAINT fk_chat_rooms_buyer  FOREIGN KEY (buyer_id)  REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_rooms_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_rooms_item   FOREIGN KEY (item_id)   REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: chat_messages
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS chat_messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    room_id     INT NOT NULL,
    sender_id   INT NOT NULL,
    message     TEXT NOT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_chat_messages_room   FOREIGN KEY (room_id)   REFERENCES chat_rooms(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: transactions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS transactions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    item_id     INT NOT NULL,
    buyer_id    INT NOT NULL,
    seller_id   INT NOT NULL,
    status      ENUM('Menunggu','Diproses','Selesai') NOT NULL DEFAULT 'Menunggu',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_transactions_item   FOREIGN KEY (item_id)   REFERENCES items(id)  ON DELETE RESTRICT,
    CONSTRAINT fk_transactions_buyer  FOREIGN KEY (buyer_id)  REFERENCES users(id)  ON DELETE RESTRICT,
    CONSTRAINT fk_transactions_seller FOREIGN KEY (seller_id) REFERENCES users(id)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: reviews
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id  INT NOT NULL UNIQUE,
    buyer_id        INT NOT NULL,
    seller_id       INT NOT NULL,
    rating          TINYINT NOT NULL,
    comment         TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_buyer       FOREIGN KEY (buyer_id)       REFERENCES users(id)        ON DELETE CASCADE,
    CONSTRAINT fk_reviews_seller      FOREIGN KEY (seller_id)      REFERENCES users(id)        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: notifications
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    type        ENUM('chat','transaction','review') NOT NULL,
    message     TEXT NOT NULL,
    link        VARCHAR(255),
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed: categories
-- --------------------------------------------------------
INSERT INTO categories (name, slug) VALUES
    ('Elektronik',           'elektronik'),
    ('Buku & Alat Tulis',    'buku-alat-tulis'),
    ('Pakaian & Fashion',    'pakaian-fashion'),
    ('Perabot & Furnitur',   'perabot-furnitur'),
    ('Olahraga & Hobi',      'olahraga-hobi'),
    ('Kendaraan & Aksesori', 'kendaraan-aksesori'),
    ('Makanan & Minuman',    'makanan-minuman'),
    ('Lainnya',              'lainnya')
ON DUPLICATE KEY UPDATE name = VALUES(name);
