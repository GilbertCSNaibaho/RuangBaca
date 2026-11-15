<?php

require_once "config/database.php";
require_once "includes/auth.php";

// Mengambil ID buku dari URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Memeriksa ID
if (!$id) {
    http_response_code(404);
    exit("ID buku tidak valid.");
}

// Mengambil informasi buku
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

// Memeriksa apakah buku ditemukan
if (!$book) {
    http_response_code(404);
    exit("Buku tidak ditemukan.");
}

// Menghitung ukuran file PDF jika tersedia
$pdfSizeLabel = "PDF · Digital";
if (!empty($book['pdf_path']) && strpos($book['pdf_path'], 'storage/books/') === 0) {
    $pdfFullPath = __DIR__ . '/storage/books/' . basename($book['pdf_path']);
    if (is_file($pdfFullPath)) {
        $bytes = filesize($pdfFullPath);
        if ($bytes >= 1048576) {
            $pdfSizeLabel = "PDF · " . number_format($bytes / 1048576, 1) . " MB";
        } elseif ($bytes >= 1024) {
            $pdfSizeLabel = "PDF · " . number_format($bytes / 1024, 0) . " KB";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($book['title']) ?> — RuangBaca</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php renderPublicHeader('catalog'); ?>

<main class="page-container">
    <div class="detail-breadcrumb">
        <a href="catalog.php">Katalog</a>
        <span>/</span>
        <a href="catalog.php?category_id=<?= (int)$book['category_id'] ?>"><?= e($book['category_name']) ?></a>
        <span>/</span>
        <strong>Detail buku</strong>
    </div>

    <div class="detail-layout">
        <!-- Left Column: Cover & Access Info -->
        <div class="panel-card detail-cover-card">
            <?= renderBookCover($book) ?>

            <div style="font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                Informasi akses
            </div>
            <div style="font-size: 12px; color: var(--text-secondary);">
                Berkas terverifikasi · <?= e($pdfSizeLabel) ?>
            </div>
        </div>

        <!-- Right Column: Book Metadata & Actions -->
        <div class="panel-card detail-info-card">
            <div class="detail-head-row">
                <div>
                    <div class="page-eyebrow"><?= e(mb_strtoupper($book['category_name'])) ?></div>
                    <h1 class="detail-title"><?= e($book['title']) ?></h1>
                    <div class="detail-author">Oleh <?= e($book['author']) ?></div>
                </div>
                <span class="pill pill-available">Tersedia</span>
            </div>

            <div class="detail-meta-grid">
                <div>
                    <div class="detail-meta-label">PENERBIT</div>
                    <div class="detail-meta-val"><?= e($book['publisher'] ?: '-') ?></div>
                </div>
                <div>
                    <div class="detail-meta-label">TAHUN TERBIT</div>
                    <div class="detail-meta-val"><?= e((string)($book['publication_year'] ?: '-')) ?></div>
                </div>
                <div>
                    <div class="detail-meta-label">FORMAT</div>
                    <div class="detail-meta-val"><?= e($pdfSizeLabel) ?></div>
                </div>
            </div>

            <h2 class="detail-about-title">Tentang buku ini</h2>
            <div class="detail-description">
                <?= nl2br(e($book['description'] ?: 'Belum ada deskripsi untuk buku ini.')) ?>
            </div>

            <div class="detail-note-box">
                <div class="detail-note-title">Akses digital tersedia</div>
                <div class="detail-note-body">
                    <?= isLoggedIn()
                        ? 'Setiap sesi membaca atau mengunduh akan tercatat otomatis pada halaman Riwayat Aktivitas Anda.'
                        : 'Login diperlukan untuk membaca dan mengunduh dokumen PDF buku ini.' ?>
                </div>
            </div>

            <div class="detail-actions">
                <?php if (isLoggedIn()): ?>
                    <a href="read_book.php?id=<?= (int)$book['book_id'] ?>" class="btn btn-primary">
                        Baca sekarang
                    </a>
                    <a href="download_book.php?id=<?= (int)$book['book_id'] ?>" class="btn btn-secondary">
                        Unduh PDF
                    </a>
                    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                        <a href="edit_book.php?id=<?= (int)$book['book_id'] ?>" class="btn btn-ghost">
                            Edit Buku
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">
                        Masuk untuk Membaca &amp; Mengunduh
                    </a>
                <?php endif; ?>
                <a href="catalog.php" class="btn btn-ghost">&larr; Kembali ke katalog</a>
            </div>
        </div>
    </div>
</main>

<script src="assets/js/app.js"></script>
</body>
</html>
