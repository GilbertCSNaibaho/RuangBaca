<?php

require_once "config/database.php";
require_once "includes/auth.php";

// Hanya Admin yang boleh mengakses halaman ini
requireRole('admin');

// Filter pencarian dan kategori
$search = trim($_GET['q'] ?? '');
$categoryId = $_GET['category_id'] ?? '';
if ($categoryId !== '' && !ctype_digit((string) $categoryId)) {
    $categoryId = '';
}

// Ambil daftar kategori untuk filter
$categories = $pdo->query("
    SELECT category_id, category_name
    FROM categories
    ORDER BY category_name ASC
")->fetchAll();

// Ambil data buku sesuai filter
$sql = "
    SELECT
        books.*,
        categories.category_name
    FROM books
    INNER JOIN categories
        ON books.category_id = categories.category_id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (books.title LIKE ? OR books.author LIKE ?)";
    $kw = "%" . $search . "%";
    $params[] = $kw;
    $params[] = $kw;
}

if ($categoryId !== '') {
    $sql .= " AND books.category_id = ?";
    $params[] = (int) $categoryId;
}

$sql .= " ORDER BY books.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Buku · RuangBaca</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="admin-layout">
        <?php renderAdminSidebar('books'); ?>

        <div class="admin-main">
            <?php renderAdminTopbar('RuangBaca   /   Panel Admin', $search); ?>

            <main class="admin-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Manajemen buku</h1>
                        <p class="page-subtitle">Kelola informasi buku, file digital, dan kategori koleksi.</p>
                    </div>
                    <a href="add_book.php" class="btn btn-primary">Tambah buku</a>
                </div>

                <?php if (isset($_GET["added"])): ?>
                    <div class="alert alert-success">Buku berhasil ditambahkan!</div>
                <?php endif; ?>

                <?php if (isset($_GET["updated"])): ?>
                    <div class="alert alert-success">Buku berhasil diperbarui!</div>
                <?php endif; ?>

                <?php if (isset($_GET["deleted"])): ?>
                    <div class="alert alert-success">Buku berhasil dihapus!</div>
                <?php endif; ?>

                <?php if (isset($_GET["error"])): ?>
                    <?php if ($_GET["error"] === "used_in_history"): ?>
                        <div class="alert alert-error">Penghapusan ditolak: Buku ini sudah memiliki riwayat aktivitas baca atau unduh anggota.</div>
                    <?php elseif ($_GET["error"] === "notfound"): ?>
                        <div class="alert alert-error">Buku yang ingin dihapus tidak ditemukan.</div>
                    <?php else: ?>
                        <div class="alert alert-error">Penghapusan buku gagal atau data tidak ditemukan.</div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Filter Bar -->
                <form method="GET" class="filter-bar">
                    <div class="filter-controls">
                        <input
                            type="text"
                            name="q"
                            class="filter-input"
                            placeholder="Cari judul atau penulis..."
                            value="<?= e($search) ?>"
                        >

                        <select name="category_id" class="filter-select" onchange="this.form.submit()">
                            <option value="">Semua kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option
                                    value="<?= (int) $cat['category_id'] ?>"
                                    <?= (string) $categoryId === (string) $cat['category_id'] ? 'selected' : '' ?>
                                >
                                    <?= e($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                        <?php if ($search !== '' || $categoryId !== ''): ?>
                            <a href="books.php" class="btn btn-secondary btn-sm">Reset</a>
                        <?php endif; ?>
                    </div>

                    <div class="filter-count"><?= count($books) ?> buku</div>
                </form>

                <!-- Tabel Manajemen Buku -->
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>BUKU</th>
                                <th>KATEGORI</th>
                                <th>TAHUN</th>
                                <th>PDF</th>
                                <th>AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($books) > 0): ?>
                                <?php foreach ($books as $index => $book): ?>
                                    <tr>
                                        <td>
                                            <div class="table-book-cell">
                                                <?= renderMiniBookCover($book, $index) ?>
                                                <div>
                                                    <a href="detail_book.php?id=<?= (int) $book['book_id'] ?>" class="table-book-title">
                                                        <?= e($book['title']) ?>
                                                    </a>
                                                    <span class="table-book-author">
                                                        <?= e($book['author']) ?>
                                                        <?= !empty($book['publisher']) ? ' · ' . e($book['publisher']) : '' ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= e($book['category_name']) ?></td>
                                        <td><?= e($book['publication_year'] ?? '-') ?></td>
                                        <td>
                                            <span class="pill pill-available">PDF</span>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="edit_book.php?id=<?= (int) $book['book_id'] ?>" class="action-edit">Edit</a>

                                                <form
                                                    action="delete_book.php"
                                                    method="POST"
                                                    style="display:inline; margin:0;"
                                                    onsubmit="return confirm('Apakah kamu yakin ingin menghapus buku ini?');"
                                                >
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                    <input type="hidden" name="book_id" value="<?= (int) $book['book_id'] ?>">
                                                    <button type="submit" class="action-delete-btn">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">Belum ada buku yang ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div class="table-footer">
                        Menampilkan <?= count($books) ?> buku dalam koleksi perpustakaan
                    </div>
                </div>
            </main>
        </div>
    </div>

<script src="assets/js/app.js"></script>
</body>
</html>
