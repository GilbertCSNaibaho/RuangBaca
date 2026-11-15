-- =============================================================================
-- RuangBaca – Sistem Informasi Perpustakaan Digital
-- File: database.sql (Struktur Tabel Utama — Tanpa Data Pribadi Pengguna)
-- Kompatibilitas: MySQL 5.7+ / MariaDB 10.4+ (XAMPP & Shared Hosting)
-- Charset: utf8mb4 / Collation: utf8mb4_unicode_ci
-- =============================================================================

SET NAMES utf8mb4;
SET TIME_ZONE = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- -----------------------------------------------------------------------------
-- 1. Tabel `categories`
-- Menyimpan daftar kategori buku perpustakaan
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(80) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Tabel `users`
-- Menyimpan akun pengguna dengan hak akses `admin` dan `member`
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','member') NOT NULL DEFAULT 'member',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Tabel `books`
-- Menyimpan metadata koleksi buku digital beserta path relatif sampul dan PDF
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `books`;
CREATE TABLE `books` (
  `book_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `author` varchar(120) NOT NULL,
  `publisher` varchar(120) DEFAULT NULL,
  `publication_year` smallint(5) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cover_path` varchar(255) DEFAULT NULL,
  `pdf_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`book_id`),
  KEY `fk_books_category` (`category_id`),
  KEY `fk_books_creator` (`created_by`),
  KEY `idx_books_title` (`title`),
  KEY `idx_books_author` (`author`),
  CONSTRAINT `fk_books_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_books_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Tabel `book_access_history`
-- Menyimpan riwayat aktivitas membaca ('read') dan mengunduh ('download') buku
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `book_access_history`;
CREATE TABLE `book_access_history` (
  `history_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `book_id` int(10) unsigned NOT NULL,
  `activity_type` enum('read','download') NOT NULL,
  `accessed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`),
  KEY `fk_history_book` (`book_id`),
  KEY `idx_history_user_accessed` (`user_id`,`accessed_at`),
  CONSTRAINT `fk_history_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
