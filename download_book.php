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

// Mengambil data PDF
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

// Memeriksa lokasi file
$prefix = "storage/books/";

if (strpos($book['pdf_path'], $prefix) !== 0) {
    http_response_code(404);
    exit("File tidak valid.");
}

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

// Memeriksa format PDF
$finfo = new finfo(FILEINFO_MIME_TYPE);

$mime = $finfo->file($filePath);

if ($mime !== "application/pdf") {
    http_response_code(404);
    exit("Format file tidak valid.");
}

// Mencatat aktivitas download
$stmt = $pdo->prepare("
    INSERT INTO book_access_history
    (user_id, book_id, activity_type)
    VALUES (?, ?, 'download')
");

$stmt->execute([
    $_SESSION['user_id'],
    $id
]);

// Mengirim file untuk diunduh
header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=\"buku-$id.pdf\"");
header("Content-Length: " . filesize($filePath));
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, no-store");

readfile($filePath);

exit;
