<?php

require_once "config/database.php";
require_once "includes/auth.php";

// Pengguna harus login
requireLogin();

// Mengambil ID buku
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(404);
    exit("ID buku tidak valid.");
}

// Mengambil informasi buku lengkap dengan kategori
$stmt = $pdo->prepare("
    SELECT
        books.*,
        categories.category_name
    FROM books
    INNER JOIN categories
        ON books.category_id = categories.category_id
    WHERE books.book_id = ?
");

$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    exit("Buku tidak ditemukan.");
}

date_default_timezone_set('Asia/Jakarta');
$openedTimeLabel = "Hari ini, " . date('H:i') . " WIB";

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membaca: <?= e($book['title']) ?> — RuangBaca</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="padding-bottom: 0;">

<div class="reader-page">
    <!-- Top Reader Header -->
    <header class="reader-header">
        <div class="reader-header-left">
            <a href="detail_book.php?id=<?= (int)$book['book_id'] ?>" class="btn btn-secondary btn-sm">
                &larr; Kembali
            </a>
            <div style="min-width: 0;">
                <div class="reader-doc-title"><?= e($book['title']) ?></div>
                <div class="reader-doc-author"><?= e($book['author']) ?> · <?= e($book['category_name']) ?></div>
            </div>
        </div>

        <div class="reader-header-desktop-dl">
            <a href="download_book.php?id=<?= (int)$book['book_id'] ?>" class="btn btn-primary btn-sm">
                Unduh PDF
            </a>
        </div>
    </header>

    <!-- Reader Toolbar -->
    <div class="reader-toolbar">
        <div style="font-size: 12px; font-weight: 600; color: var(--text-secondary);">
            Mode Baca Digital · RuangBaca
        </div>

        <div class="reader-toolbar-controls">
            <div class="reader-zoom-stepper">
                <button type="button" id="reader-zoom-out" class="reader-zoom-btn" aria-label="Perkecil tampilan">&minus;</button>
                <select id="reader-zoom-select" class="form-select" style="height: 30px; width: auto; border: none; font-size: 12px; font-weight: 600; padding: 0 8px;" aria-label="Perbesaran halaman">
                    <option value="85">85%</option>
                    <option value="100" selected>100%</option>
                    <option value="110">110%</option>
                    <option value="125">125%</option>
                    <option value="150">150%</option>
                </select>
                <button type="button" id="reader-zoom-in" class="reader-zoom-btn" aria-label="Perbesar tampilan">&plus;</button>
            </div>

            <button type="button" id="reader-fit-btn" class="btn btn-secondary btn-sm" style="height: 34px;">
                Lebar halaman
            </button>
        </div>
    </div>

    <!-- 3-Column Reader Workspace -->
    <div class="reader-workspace" id="reader-workspace">
        <!-- Left Sidebar: Metadata & Navigation -->
        <aside class="reader-side-card">
            <div style="font-size: 11px; font-weight: 700; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 12px;">
                INFORMASI KOLEKSI
            </div>

            <div class="reader-toc-item active">
                1. Dokumen Utama PDF
            </div>
            <div class="reader-toc-item">
                Kategori: <?= e($book['category_name']) ?>
            </div>
            <div class="reader-toc-item">
                Penerbit: <?= e($book['publisher'] ?: '-') ?>
            </div>
            <div class="reader-toc-item">
                Tahun: <?= e((string)($book['publication_year'] ?: '-')) ?>
            </div>

            <div style="border-top: 1px solid var(--border-soft); margin: 16px 0; padding-top: 14px;">
                <div style="font-size: 11px; font-weight: 700; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 10px;">
                    NAVIGASI CEPAT
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="detail_book.php?id=<?= (int)$book['book_id'] ?>" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                        Detail Buku
                    </a>
                    <a href="catalog.php" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                        Katalog Buku
                    </a>
                    <a href="history.php" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                        Riwayat Aktivitas
                    </a>
                </div>
            </div>
        </aside>

        <!-- Center PDF Viewer -->
        <section class="reader-canvas-frame">
            <iframe
                id="reader-iframe"
                class="reader-iframe"
                src="stream_book.php?id=<?= (int)$book['book_id'] ?>"
                title="Pembaca buku digital <?= e($book['title']) ?>"
            ></iframe>
        </section>

        <!-- Right Sidebar: Reading Status -->
        <aside class="reader-side-card">
            <div style="font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 14px;">
                Informasi bacaan
            </div>

            <div style="margin-bottom: 14px;">
                <div style="font-size: 11px; color: var(--text-secondary); margin-bottom: 4px;">Status</div>
                <span class="pill pill-available">Sedang dibaca</span>
            </div>

            <div style="margin-bottom: 16px;">
                <div style="font-size: 11px; color: var(--text-secondary); margin-bottom: 4px;">Terakhir dibuka</div>
                <div style="font-size: 12px; font-weight: 600; color: var(--text-primary);">
                    <?= e($openedTimeLabel) ?>
                </div>
            </div>

            <div style="background: var(--surface-canvas); border-radius: 10px; padding: 12px; font-size: 12px; color: var(--text-secondary); line-height: 1.55;">
                Aktivitas membaca buku ini telah dicatat secara otomatis ke dalam riwayat akun Anda.
            </div>
        </aside>
    </div>

    <!-- Mobile Sticky Bottom Bar (Figma 03 · Mobile / PDF Reader) -->
    <div class="reader-mobile-bottom">
        <div class="reader-mobile-note">Progress membaca tersimpan saat file diakses.</div>
        <a href="download_book.php?id=<?= (int)$book['book_id'] ?>" class="btn btn-primary btn-block">
            Unduh PDF
        </a>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
