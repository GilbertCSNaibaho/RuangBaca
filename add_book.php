<?php

require_once "config/database.php";
require_once "includes/auth.php";

// Hanya Admin yang boleh menambahkan buku
requireRole('admin');

// Mengambil daftar kategori
$stmt = $pdo->query("
    SELECT category_id, category_name
    FROM categories
    ORDER BY category_name ASC
");

$categories = $stmt->fetchAll();

$errors = [];

// Data formulir
$title = "";
$author = "";
$publisher = "";
$publication_year = "";
$description = "";
$category_id = "";

// Proses ketika formulir dikirim
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verifyCsrf();

    $title = trim($_POST["title"] ?? "");
    $author = trim($_POST["author"] ?? "");
    $publisher = trim($_POST["publisher"] ?? "");
    $publication_year = trim($_POST["publication_year"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $category_id = (int) ($_POST["category_id"] ?? 0);

    // Validasi data teks
    if ($title === "") {
        $errors[] = "Judul buku wajib diisi.";
    }

    if ($author === "") {
        $errors[] = "Nama penulis wajib diisi.";
    }

    // Validasi kategori
    $validCategoryIds = array_map(
        'intval',
        array_column($categories, 'category_id')
    );

    if (!in_array($category_id, $validCategoryIds, true)) {
        $errors[] = "Kategori buku tidak valid.";
    }

    // Validasi tahun terbit
    if (
        $publication_year !== "" &&
        (
            !ctype_digit($publication_year) ||
            (int)$publication_year < 1000 ||
            (int)$publication_year > 9999
        )
    ) {
        $errors[] = "Tahun terbit harus berupa 4 digit angka.";
    }

    // Periksa file PDF
    $pdfFile = $_FILES["pdf_file"] ?? null;

    if (!$pdfFile || $pdfFile["error"] !== UPLOAD_ERR_OK) {
        $errors[] = "File PDF wajib diunggah.";
    } elseif ($pdfFile["size"] > 10 * 1024 * 1024) {
        $errors[] = "Ukuran PDF maksimal 10 MB.";
    }

    // Periksa gambar sampul (opsional)
    $coverFile = $_FILES["cover_file"] ?? null;
    $hasCover = $coverFile &&
                $coverFile["error"] !== UPLOAD_ERR_NO_FILE;

    // Validasi jenis file menggunakan MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    if ($pdfFile && $pdfFile["error"] === UPLOAD_ERR_OK) {

        $pdfMime = $finfo->file($pdfFile["tmp_name"]);

        if ($pdfMime !== "application/pdf") {
            $errors[] = "File buku harus berformat PDF.";
        }
    }

    $coverExtension = null;

    if ($hasCover) {

        if ($coverFile["error"] !== UPLOAD_ERR_OK) {
            $errors[] = "Gagal mengunggah gambar sampul.";

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

    // Simpan file dan data jika validasi berhasil
    if (empty($errors)) {

        $pdfDirectory = __DIR__ . "/storage/books/";
        $coverDirectory = __DIR__ . "/storage/covers/";

        $pdfName = bin2hex(random_bytes(16)) . ".pdf";

        $coverName = $hasCover
            ? bin2hex(random_bytes(16)) . "." . $coverExtension
            : null;

        // Pastikan folder tersedia
        if (
            !is_dir($pdfDirectory) ||
            !is_dir($coverDirectory) ||
            !is_writable($pdfDirectory) ||
            !is_writable($coverDirectory)
        ) {
            $errors[] = "Folder penyimpanan tidak tersedia atau tidak dapat ditulis.";
        } else {

            $pdfMoved = move_uploaded_file(
                $pdfFile["tmp_name"],
                $pdfDirectory . $pdfName
            );

            $coverMoved = false;

            if (!$pdfMoved) {
                $errors[] = "Gagal menyimpan file PDF.";
            }

            if ($pdfMoved && $hasCover) {

                $coverMoved = move_uploaded_file(
                    $coverFile["tmp_name"],
                    $coverDirectory . $coverName
                );

                if (!$coverMoved) {
                    $errors[] = "Gagal menyimpan gambar sampul.";
                }
            }

            // Simpan informasi buku ke database
            if (empty($errors)) {

                try {

                    $pdfPath = "storage/books/" . $pdfName;

                    $coverPath = $hasCover
                        ? "storage/covers/" . $coverName
                        : null;

                    $stmt = $pdo->prepare("
                        INSERT INTO books (
                            category_id,
                            created_by,
                            title,
                            author,
                            publisher,
                            publication_year,
                            description,
                            cover_path,
                            pdf_path
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $category_id,
                        $_SESSION["user_id"],
                        $title,
                        $author,
                        $publisher ?: null,
                        $publication_year !== ""
                            ? (int)$publication_year
                            : null,
                        $description ?: null,
                        $coverPath,
                        $pdfPath
                    ]);

                    header("Location: books.php?added=1");
                    exit;

                } catch (PDOException $e) {

                    // Bersihkan file apabila penyimpanan database gagal
                    @unlink($pdfDirectory . $pdfName);

                    if ($hasCover && $coverMoved) {
                        @unlink($coverDirectory . $coverName);
                    }

                    $errors[] = "Buku gagal disimpan ke database.";
                }
            }

            // Bersihkan PDF jika upload sampul gagal
            if ($pdfMoved && !empty($errors)) {
                @unlink($pdfDirectory . $pdfName);
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Buku Baru — RuangBaca</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php renderAdminSidebar('books'); ?>

    <div class="admin-main">
        <?php renderAdminTopbar('Manajemen Buku / Tambah Buku'); ?>

        <main class="admin-content">
            <div class="page-head">
                <div>
                    <div class="page-eyebrow">MANAJEMEN KOLEKSI</div>
                    <h1 class="page-title">Tambah buku baru</h1>
                    <p class="page-subtitle">Lengkapi metadata, sampul, dan berkas PDF sebelum menerbitkan ke katalog.</p>
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
                                    placeholder="Contoh: Pengantar Struktur Data"
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
                                        placeholder="Dr. Hendra Wijaya"
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
                                        placeholder="2024"
                                        value="<?= e($publication_year) ?>"
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
                                        placeholder="Gramedia Pustaka"
                                        value="<?= e($publisher) ?>"
                                    >
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="category_id">Kategori</label>
                                    <select id="category_id" name="category_id" class="form-select" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option
                                                value="<?= (int)$category['category_id'] ?>"
                                                <?= (string)$category_id === (string)$category['category_id'] ? 'selected' : '' ?>
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
                                    placeholder="Tuliskan ringkasan isi buku, topik utama, dan sasaran pembaca..."
                                ><?= e($description) ?></textarea>
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label class="form-label">Sampul buku (opsional)</label>
                                    <label class="upload-dropzone" for="input-cover-file">
                                        <span class="upload-dropzone-title" id="cover-file-label">Pilih gambar sampul</span>
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
                                    <label class="form-label">Berkas buku PDF (wajib)</label>
                                    <label class="upload-dropzone" for="input-pdf-file">
                                        <span class="upload-dropzone-title" id="pdf-file-label">Pilih berkas PDF</span>
                                        <span class="upload-dropzone-sub">Format PDF · Maks. 10 MB</span>
                                        <input
                                            type="file"
                                            id="input-pdf-file"
                                            name="pdf_file"
                                            accept=".pdf,application/pdf"
                                            required
                                        >
                                    </label>
                                </div>
                            </div>

                            <div style="display: flex; gap: 12px; margin-top: 8px;">
                                <button type="submit" class="btn btn-primary">Simpan buku</button>
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
                        <div class="preview-cover-wrapper" id="book-cover-preview-box">
                            <span class="preview-cover-tag">PRATINJAU</span>
                            <div class="preview-cover-title" id="preview-cover-title">
                                <?= $title !== '' ? e($title) : 'Judul Buku Baru' ?>
                            </div>
                        </div>

                        <div class="guidance-box">
                            <div class="guidance-box-title">Sebelum menyimpan</div>
                            <p class="guidance-box-text">
                                Pastikan berkas PDF tidak terkunci kata sandi dan sampul memiliki rasio potret agar tampil rapi di katalog.
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
