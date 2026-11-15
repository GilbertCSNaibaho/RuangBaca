<?php

require_once "config/database.php";
require_once "includes/auth.php";

// Hanya pengguna yang login dapat membaca buku
requireLogin();

// Mengambil ID buku
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(404);
    exit("ID buku tidak valid.");
}

// Mengambil lokasi PDF
$stmt = $pdo->prepare("
    SELECT pdf_path
    FROM books
    WHERE book_id = ?
");

$stmt->execute([$id]);
$book = $stmt->fetch();

if (!$book) {
    http_response_code(404);
    exit("Buku tidak ditemukan.");
}

// Memastikan file berada di folder storage/books
$prefix = "storage/books/";

if (strpos($book['pdf_path'], $prefix) !== 0) {
    http_response_code(404);
    exit("File tidak valid.");
}

// Memeriksa lokasi file
$storageDirectory = realpath(
    __DIR__ . "/storage/books"
);

if ($storageDirectory === false) {
    http_response_code(404);
    exit("Folder buku tidak ditemukan.");
}

$fileName = basename($book['pdf_path']);

$filePath = realpath(
    $storageDirectory . DIRECTORY_SEPARATOR . $fileName
);

if (
    $filePath === false ||
    dirname($filePath) !== $storageDirectory ||
    !is_file($filePath)
) {
    http_response_code(404);
    exit("File PDF tidak ditemukan.");
}

// Memeriksa format file
$finfo = new finfo(FILEINFO_MIME_TYPE);

$mime = $finfo->file($filePath);

if ($mime !== "application/pdf") {
    http_response_code(404);
    exit("Format file tidak valid.");
}

// Simpan riwayat membaca dengan kebijakan deduplikasi:
// Satu aktivitas 'read' per pengguna (user_id) dan buku (book_id) dalam jendela waktu 10 menit
// agar refresh halaman atau sub-request pemuatan PDF tidak menghasilkan duplikasi catatan.
$userId = (int) $_SESSION['user_id'];

$checkHistoryStmt = $pdo->prepare("
    SELECT history_id
    FROM book_access_history
    WHERE user_id = ?
      AND book_id = ?
      AND activity_type = ?
      AND accessed_at >= (NOW() - INTERVAL 10 MINUTE)
    LIMIT 1
");
$checkHistoryStmt->execute([$userId, $id, 'read']);

if (!$checkHistoryStmt->fetch()) {
    $insertHistoryStmt = $pdo->prepare("
        INSERT INTO book_access_history
        (user_id, book_id, activity_type)
        VALUES (?, ?, ?)
    ");
    $insertHistoryStmt->execute([
        $userId,
        $id,
        'read'
    ]);
}

// Kirim PDF ke browser
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"buku-$id.pdf\"");
header("Content-Length: " . filesize($filePath));
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: frame-ancestors 'self'");
header("Cache-Control: private, no-store");

readfile($filePath);

exit;
