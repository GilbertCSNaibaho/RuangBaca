<?php

require_once "config/database.php";
require_once "includes/auth.php";

requireRole('admin');

// Memeriksa ID buku
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    exit("ID buku tidak valid.");
}

// Mengambil data buku
$stmt = $pdo->prepare("
    SELECT * FROM books
    WHERE book_id = ?
");

$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    exit("Buku tidak ditemukan.");
}

// Mengambil kategori
$stmt = $pdo->query("
    SELECT * FROM categories
    ORDER BY category_name ASC
");

$categories = $stmt->fetchAll();

$errors = [];

$title = $book['title'];
$author = $book['author'];
$publisher = $book['publisher'] ?? '';
$publication_year = $book['publication_year'] ?? '';
$description = $book['description'] ?? '';
$category_id = (int)$book['category_id'];

// Proses update
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verifyCsrf();

    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $publication_year = trim($_POST['publication_year'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);

    // Validasi judul dan penulis
    if ($title === '') {
        $errors[] = "Judul buku wajib diisi.";
    }

    if ($author === '') {
        $errors[] = "Nama penulis wajib diisi.";
    }

    // Validasi kategori
    $validCategories = array_map(
        'intval',
        array_column($categories, 'category_id')
    );

    if (!in_array($category_id, $validCategories, true)) {
        $errors[] = "Kategori tidak valid.";
    }

    // Validasi tahun
    if (
        $publication_year !== '' &&
        (
            !ctype_digit($publication_year) ||
            (int)$publication_year < 1000 ||
            (int)$publication_year > 9999
        )
    ) {
        $errors[] = "Tahun terbit tidak valid.";
    }

    // Pemeriksaan opsional ganti sampul atau ganti PDF
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $coverFile = $_FILES["cover_file"] ?? null;
    $hasNewCover = $coverFile && $coverFile["error"] !== UPLOAD_ERR_NO_FILE;
    $coverExtension = null;

    if ($hasNewCover) {
        if ($coverFile["error"] !== UPLOAD_ERR_OK) {
            $errors[] = "Gagal mengunggah gambar sampul baru.";
        } elseif ($coverFile["size"] > 2 * 1024 * 1024) {
            $errors[] = "Ukuran sampul maksimal 2 MB.";
        } else {
            $coverMime = $finfo->file($coverFile["tmp_name"]);
            $allowedCovers = [
                "image/jpeg" => "jpg",
                "image/png" => "png",
                "image/webp" => "webp"
            ];
            if (!isset($allowedCovers[$coverMime])) {
                $errors[] = "Sampul harus berupa JPG, PNG, atau WEBP.";
            } else {
                $coverExtension = $allowedCovers[$coverMime];
            }
        }
    }

    $pdfFile = $_FILES["pdf_file"] ?? null;
    $hasNewPdf = $pdfFile && $pdfFile["error"] !== UPLOAD_ERR_NO_FILE;

    if ($hasNewPdf) {
        if ($pdfFile["error"] !== UPLOAD_ERR_OK) {
            $errors[] = "Gagal mengunggah file PDF baru.";
        } elseif ($pdfFile["size"] > 10 * 1024 * 1024) {
            $errors[] = "Ukuran PDF maksimal 10 MB.";
        } else {
            $pdfMime = $finfo->file($pdfFile["tmp_name"]);
            if ($pdfMime !== "application/pdf") {
                $errors[] = "File buku harus berformat PDF.";
            }
        }
    }

    // Simpan perubahan
    if (empty($errors)) {

        $newCoverPath = $book['cover_path'];
        $newPdfPath = $book['pdf_path'];

        if ($hasNewCover) {
            $coverDirectory = __DIR__ . "/storage/covers/";
            $coverName = bin2hex(random_bytes(16)) . "." . $coverExtension;
            if (move_uploaded_file($coverFile["tmp_name"], $coverDirectory . $coverName)) {
                if (!empty($book['cover_path']) && strpos($book['cover_path'], 'storage/covers/') === 0) {
                    $oldCover = __DIR__ . '/storage/covers/' . basename($book['cover_path']);
                    if (is_file($oldCover)) {
                        @unlink($oldCover);
                    }
                }
                $newCoverPath = "storage/covers/" . $coverName;
            } else {
                $errors[] = "Gagal menyimpan gambar sampul baru.";
            }
        }

        if (empty($errors) && $hasNewPdf) {
            $pdfDirectory = __DIR__ . "/storage/books/";
            $pdfName = bin2hex(random_bytes(16)) . ".pdf";
            if (move_uploaded_file($pdfFile["tmp_name"], $pdfDirectory . $pdfName)) {
                if (!empty($book['pdf_path']) && strpos($book['pdf_path'], 'storage/books/') === 0) {
                    $oldPdf = __DIR__ . '/storage/books/' . basename($book['pdf_path']);
                    if (is_file($oldPdf)) {
                        @unlink($oldPdf);
                    }
                }
                $newPdfPath = "storage/books/" . $pdfName;
            } else {
                $errors[] = "Gagal menyimpan file PDF baru.";
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                UPDATE books SET
                    category_id = ?,
                    title = ?,
                    author = ?,
                    publisher = ?,
                    publication_year = ?,
                    description = ?,
                    cover_path = ?,
                    pdf_path = ?
                WHERE book_id = ?
            ");

            $stmt->execute([
                $category_id,
                $title,
                $author,
                $publisher ?: null,
                $publication_year !== ''
                    ? (int)$publication_year
                    : null,
                $description ?: null,
                $newCoverPath,
                $newPdfPath,
                $id
            ]);

            header("Location: books.php?updated=1");
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Informasi Buku — RuangBaca</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php renderAdminSidebar('books'); ?>

    <div class="admin-main">
        <?php renderAdminTopbar('Manajemen Buku / Edit Buku'); ?>

        <main class="admin-content">
            <div class="page-head">
                <div>
                    <div class="page-eyebrow">MANAJEMEN KOLEKSI</div>
                    <h1 class="page-title">Edit informasi buku</h1>
                    <p class="page-subtitle">Perbarui metadata, sampul, atau berkas PDF untuk koleksi ini.</p>
                </div>
                <div class="page-actions">
                    <a href="books.php" class="btn btn-outline btn-sm">&larr; Kembali ke daftar</a>
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
                        <h2 class="panel-card-title">Informasi buku</h2>
                    </div>
                    <div class="panel-card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                            <div class="form-group">
                                <label class="form-label" for="input-book-title">Judul buku</label>
                                <input
                                    type="text"
                                    id="input-book-title"
                                    name="title"
                                    class="form-control"
                                    value="<?= e($title) ?>"
                                    required
                                >
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label class="form-label" for="author">Penulis</label>
                                    <input
                                        type="text"
                                        id="author"
                                        name="author"
                                        class="form-control"
                                        value="<?= e($author) ?>"
                                        required
                                    >
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="publication_year">Tahun terbit</label>
                                    <input
                                        type="number"
                                        id="publication_year"
                                        name="publication_year"
                                        class="form-control"
                                        min="1000"
                                        max="9999"
                                        value="<?= e((string)$publication_year) ?>"
                                    >
                                </div>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label class="form-label" for="publisher">Penerbit</label>
                                    <input
                                        type="text"
                                        id="publisher"
                                        name="publisher"
                                        class="form-control"
                                        value="<?= e($publisher) ?>"
                                    >
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="category_id">Kategori</label>
                                    <select id="category_id" name="category_id" class="form-select" required>
                                        <?php foreach ($categories as $category): ?>
                                            <option
                                                value="<?= (int)$category['category_id'] ?>"
                                                <?= $category_id === (int)$category['category_id'] ? 'selected' : '' ?>
                                            >
                                                <?= e($category['category_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="description">Deskripsi singkat</label>
                                <textarea
                                    id="description"
                                    name="description"
                                    class="form-textarea"
                                    rows="4"
                                ><?= e($description) ?></textarea>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label class="form-label">Ganti sampul buku (opsional)</label>
                                    <label class="upload-dropzone" for="input-cover-file">
                                        <span class="upload-dropzone-title" id="cover-file-label">
                                            <?= !empty($book['cover_path']) ? 'Ganti sampul tersimpan' : 'Pilih gambar sampul' ?>
                                        </span>
                                        <span class="upload-dropzone-sub">JPG, PNG, WEBP · Maks. 2 MB</span>
                                        <input
                                            type="file"
                                            id="input-cover-file"
                                            name="cover_file"
                                            accept=".jpg,.jpeg,.png,.webp"
                                        >
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Ganti berkas PDF (opsional)</label>
                                    <label class="upload-dropzone" for="input-pdf-file">
                                        <span class="upload-dropzone-title" id="pdf-file-label">Biarkan kosong jika tidak diganti</span>
                                        <span class="upload-dropzone-sub">Format PDF · Maks. 10 MB</span>
                                        <input
                                            type="file"
                                            id="input-pdf-file"
                                            name="pdf_file"
                                            accept=".pdf,application/pdf"
                                        >
                                    </label>
                                </div>
                            </div>

                            <div style="display: flex; gap: 12px; margin-top: 8px;">
                                <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                                <a href="books.php" class="btn btn-outline">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Live Cover Preview & Guidance -->
                <div class="panel-card">
                    <div class="panel-card-header">
                        <h2 class="panel-card-title">Pratinjau sampul</h2>
                    </div>
                    <div class="panel-card-body">
                        <?php if (!empty($book['cover_path'])): ?>
                            <div class="preview-cover-wrapper" id="book-cover-preview-box" style="padding: 0; overflow: hidden;">
                                <img
                                    src="cover.php?id=<?= (int)$book['book_id'] ?>"
                                    alt="Sampul <?= e($title) ?>"
                                    style="width:100%;height:100%;object-fit:cover;display:block;"
                                >
                            </div>
                        <?php else: ?>
                            <div class="preview-cover-wrapper" id="book-cover-preview-box">
                                <span class="preview-cover-tag">PRATINJAU</span>
                                <div class="preview-cover-title" id="preview-cover-title">
                                    <?= e($title) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="guidance-box">
                            <div class="guidance-box-title">Sebelum menyimpan</div>
                            <p class="guidance-box-text">
                                Perubahan informasi langsung diterapkan pada halaman katalog dan detail buku.
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
