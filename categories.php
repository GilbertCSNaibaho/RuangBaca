<?php

require_once "config/database.php";
require_once "includes/auth.php";

requireRole('admin');

// Mengambil data kategori dan jumlah buku
$stmt = $pdo->query("
    SELECT
        categories.*,
        COUNT(books.book_id) AS total_books
    FROM categories
    LEFT JOIN books
        ON categories.category_id = books.category_id
    GROUP BY categories.category_id
    ORDER BY categories.category_name ASC
");

$categories = $stmt->fetchAll();

$totalCategories = count($categories);
$totalBooksAcrossCategories = 0;
$emptyCategories = 0;

foreach ($categories as $cat) {
    $cnt = (int) $cat['total_books'];
    $totalBooksAcrossCategories += $cnt;
    if ($cnt === 0) {
        $emptyCategories++;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori · RuangBaca</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="admin-layout">
        <?php renderAdminSidebar('categories'); ?>

        <div class="admin-main">
            <?php renderAdminTopbar('RuangBaca   /   Panel Admin'); ?>

            <main class="admin-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Manajemen kategori</h1>
                        <p class="page-subtitle">Kelompokkan koleksi agar anggota lebih mudah menemukan buku.</p>
                    </div>
                    <a href="add_category.php" class="btn btn-primary">Tambah kategori</a>
                </div>

                <?php if (isset($_GET['added'])): ?>
                    <div class="alert alert-success">Kategori berhasil ditambahkan!</div>
                <?php endif; ?>

                <?php if (isset($_GET['updated'])): ?>
                    <div class="alert alert-success">Kategori berhasil diperbarui!</div>
                <?php endif; ?>

                <?php if (isset($_GET['deleted'])): ?>
                    <div class="alert alert-success">Kategori berhasil dihapus!</div>
                <?php endif; ?>

                <?php if (isset($_GET['error']) && $_GET['error'] === 'used'): ?>
                    <div class="alert alert-error">
                        Kategori tidak dapat dihapus karena masih digunakan oleh buku.
                    </div>
                <?php endif; ?>

                <!-- 3 Category Stat Cards -->
                <div class="stat-grid-3">
                    <div class="stat-card stat-card-inline">
                        <span class="stat-value" style="margin:0;"><?= str_pad((string) $totalCategories, 2, '0', STR_PAD_LEFT) ?></span>
                        <span class="stat-label">Kategori aktif</span>
                    </div>

                    <div class="stat-card stat-card-inline">
                        <span class="stat-value" style="margin:0;"><?= str_pad((string) $totalBooksAcrossCategories, 2, '0', STR_PAD_LEFT) ?></span>
                        <span class="stat-label">Total buku</span>
                    </div>

                    <div class="stat-card stat-card-inline">
                        <span class="stat-value" style="margin:0;"><?= str_pad((string) $emptyCategories, 2, '0', STR_PAD_LEFT) ?></span>
                        <span class="stat-label">Kategori belum terisi</span>
                    </div>
                </div>

                <!-- Category Table -->
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>KATEGORI</th>
                                <th>DESKRIPSI</th>
                                <th>JUMLAH BUKU</th>
                                <th>AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($categories) > 0): ?>
                                <?php
                                $tones = ['', 'tone-1', 'tone-2'];
                                foreach ($categories as $index => $category):
                                    $initial = userInitials($category['category_name'], 1);
                                    $tone = $tones[$index % count($tones)];
                                ?>
                                    <tr>
                                        <td>
                                            <div class="category-icon-cell">
                                                <div class="category-badge <?= $tone ?>">
                                                    <?= e($initial) ?>
                                                </div>
                                                <span class="table-book-title" style="margin:0;">
                                                    <?= e($category['category_name']) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td><?= e($category['description'] ?: '-') ?></td>
                                        <td>
                                            <strong style="color:var(--text-primary);"><?= (int) $category['total_books'] ?></strong>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="edit_category.php?id=<?= (int) $category['category_id'] ?>" class="action-edit">
                                                    Edit
                                                </a>

                                                <form
                                                    action="delete_category.php"
                                                    method="POST"
                                                    style="display:inline; margin:0;"
                                                    onsubmit="return confirm('Yakin ingin menghapus kategori ini?');"
                                                >
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                    <input type="hidden" name="category_id" value="<?= (int) $category['category_id'] ?>">
                                                    <button type="submit" class="action-delete-btn">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">Belum ada kategori.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

<script src="assets/js/app.js"></script>
</body>
</html>
