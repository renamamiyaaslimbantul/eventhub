-- ================================================================
-- EventHub — Skema Database Milestone 2
-- Database: MySQL 8.x / MariaDB 10.6+
-- Jalankan: mysql -u root -p < database/migrations/001_init.sql
-- Seed data: php database/seeders/seed.php
-- ================================================================

CREATE DATABASE IF NOT EXISTS eventhub
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE eventhub;

-- ----------------------------------------------------------
-- 1. Tabel users
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(255)  NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('admin', 'participant') NOT NULL DEFAULT 'participant',
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role  (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. Tabel events
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organizer_id BIGINT UNSIGNED NOT NULL,
    title        VARCHAR(255)    NOT NULL,
    description  TEXT            NOT NULL,
    category     ENUM('Webinar','Workshop','Seminar') NOT NULL,
    event_date   DATE            NOT NULL,
    location     VARCHAR(255)    NOT NULL,
    max_seats    INT UNSIGNED    NOT NULL DEFAULT 100,
    poster_url   VARCHAR(500)    NULL,
    created_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_organizer
        FOREIGN KEY (organizer_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_events_category  (category),
    INDEX idx_events_event_date (event_date),
    INDEX idx_events_organizer  (organizer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. Tabel registrations
--    UNIQUE (user_id, event_id) mencegah duplikasi pendaftaran
--    di level database (backup dari validasi aplikasi).
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS registrations (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id       BIGINT UNSIGNED NOT NULL,
    user_id        BIGINT UNSIGNED NOT NULL,
    status         ENUM('registered','attended','canceled') NOT NULL DEFAULT 'registered',
    registered_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reg_event
        FOREIGN KEY (event_id) REFERENCES events(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reg_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_user_event (user_id, event_id),
    INDEX idx_reg_event  (event_id),
    INDEX idx_reg_user   (user_id),
    INDEX idx_reg_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
