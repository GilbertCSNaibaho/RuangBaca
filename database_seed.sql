-- =============================================================================
-- RuangBaca – Sistem Informasi Perpustakaan Digital
-- File: database_seed.sql (Data Awal Kategori Sintetis — Aman untuk Publik/GitHub)
-- Catatan:
--   - Jalankan file ini SETELAH mengimpor `database.sql`.
--   - Hanya berisi data master kategori buku umum.
--   - Tidak mengandung akun pengguna, email, password, hash password, maupun
--     riwayat aktivitas pengguna.
--   - Untuk membuat akun Admin pertama secara aman, ikuti petunjuk pada README.md
--     (Bab 12: Cara Membuat Akun Admin Pertama Secara Aman).
-- =============================================================================

SET NAMES utf8mb4;
SET TIME_ZONE = '+00:00';

-- -----------------------------------------------------------------------------
-- Data Awal Kategori Buku (`categories`)
-- -----------------------------------------------------------------------------
INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Teknologi', 'Buku seputar teknologi dan komputer'),
(2, 'Pemrograman', 'Buku bahasa pemrograman dan pengembangan software'),
(3, 'Pendidikan', 'Buku pembelajaran dan pendidikan'),
(4, 'Sains', 'Buku ilmu pengetahuan dan penelitian'),
(5, 'Novel', 'Buku cerita dan karya sastra'),
(6, 'Bisnis', 'Buku bisnis dan kewirausahaan');
