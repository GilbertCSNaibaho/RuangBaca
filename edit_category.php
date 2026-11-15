<?php

require_once "config/database.php";
require_once "includes/auth.php";

requireRole('admin');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    exit("ID kategori tidak valid.");
}

// Mengambil kategori beserta jumlah buku
$stmt = $pdo->prepare("
    SELECT
        categories.*,
        COUNT(books.book_id) AS total_books
    FROM categories
    LEFT JOIN books
        ON categories.category_id = books.category_id
    WHERE categories.category_id = ?
    GROUP BY categories.category_id
");

$stmt->execute([$id]);

$category = $stmt->fetch();

if (!$category) {
    exit("Kategori tidak ditemukan.");
}

$errors = [];

$name = $category['category_name'];
$description = $category['description'] ?? '';
$totalBooks = (int)($category['total_books'] ?? 0);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verifyCsrf();

    $name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === "") {
        $errors[] = "Nama kategori wajib diisi.";
    }

    if (strLength($name) > 80) {
        $errors[] = "Nama kategori maksimal 80 karakter.";
    }

    // Periksa nama kategori duplikat
    if (empty($errors)) {

        $stmt = $pdo->prepare("
            SELECT category_id
            FROM categories
            WHERE category_name = ?
            AND category_id != ?
        ");

        $stmt->execute([$name, $id]);

        if ($stmt->fetch()) {
            $errors[] = "Nama kategori sudah digunakan.";
        }
    }

    // Update data kategori
    if (empty($errors)) {

        $stmt = $pdo->prepare("
            UPDATE categories
            SET category_name = ?,
                description = ?
            WHERE category_id = ?
        ");

        $stmt->execute([
            $name,
            $description ?: null,
            $id
        ]);

        header("Location: categories.php?updated=1");
        exit;
    }
}

$initial = $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : 'K';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kategori Buku — RuangBaca</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php renderAdminSidebar('categories'); ?>

    <div class="admin-main">
        <?php renderAdminTopbar('Kategori Buku / Edit Kategori'); ?>

        <main class="admin-content">
            <div class="page-head">
                <div>
                    <div class="page-eyebrow">MANAJEMEN KATEGORI</div>
                    <h1 class="page-title">Edit kategori buku</h1>
                    <p class="page-subtitle">Perbarui nama dan deskripsi cakupan topik untuk kategori ini.</p>
                </div>
                <div class="page-actions">
                    <a href="categories.php" class="btn btn-outline btn-sm">&larr; Kembali ke kategori</a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 18px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="form-split-layout">
                <!-- Left Column: Form -->
                <div class="panel-card">
                    <div class="panel-card-header">
                        <h2 class="panel-card-title">Detail kategori</h2>
                    </div>
                    <div class="panel-card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                            <div class="form-group">
                                <label class="form-label" for="input-category-name">Nama kategori</label>
                                <input
                                    type="text"
                                    id="input-category-name"
                                    name="category_name"
                                    class="form-control"
                                    maxlength="80"
                                    value="<?= e($name) ?>"
                                    required
                                >
                                <div class="form-hint">Maksimal 80 karakter · Nama kategori harus unik.</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="input-category-desc">Deskripsi kategori</label>
                                <textarea
                                    id="input-category-desc"
                                    name="description"
                                    class="form-textarea"
                                    rows="5"
                                ><?= e($description) ?></textarea>
                            </div>

                            <div class="panel-card" style="background: #F6F7F8; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: #12253C;">Status kategori</div>
                                    <div style="font-size: 11px; color: #738296;">Tampilkan sebagai filter di halaman katalog anggota</div>
                                </div>
                                <span class="pill pill-success">Aktif</span>
                            </div>

                            <div style="display: flex; gap: 12px;">
                                <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                                <a href="categories.php" class="btn btn-outline">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Live Preview -->
                <div class="panel-card">
                    <div class="panel-card-header">
                        <h2 class="panel-card-title">Pratinjau tampilan</h2>
                    </div>
                    <div class="panel-card-body">
                        <div class="panel-card" style="background: #F6F7F8; padding: 18px;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                <div class="cat-initial-badge" id="preview-category-initial"><?= e($initial) ?></div>
                                <div>
                                    <div style="font-size: 14px; font-weight: 700; color: #12253C;" id="preview-category-name">
                                        <?= e($name) ?>
                                    </div>
                                    <div style="font-size: 11px; color: #738296;"><?= $totalBooks ?> buku terhubung</div>
                                </div>
                            </div>
                            <div style="font-size: 12px; color: #3D4E63; line-height: 1.55;" id="preview-category-desc">
                                <?= $description !== '' ? e($description) : 'Belum ada deskripsi kategori.' ?>
                            </div>
                        </div>

                        <div class="guidance-box">
                            <div class="guidance-box-title">Aturan penghapusan</div>
                            <p class="guidance-box-text">
                                Kategori yang sudah memiliki buku terhubung tidak dapat dihapus sebelum buku dipindahkan ke kategori lain.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
