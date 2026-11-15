<?php

require_once "config/database.php";

// Mengambil ID buku
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(404);
    exit;
}

// Mengambil lokasi gambar
$stmt = $pdo->prepare("
    SELECT cover_path
    FROM books
    WHERE book_id = ?
");

$stmt->execute([$id]);

$book = $stmt->fetch();

if (!$book || empty($book['cover_path'])) {
    http_response_code(404);
    exit;
}

// Memastikan file berasal dari folder sampul
$prefix = 'storage/covers/';

if (strpos($book['cover_path'], $prefix) !== 0) {
    http_response_code(404);
    exit;
}

// Mengambil nama file dengan aman
$fileName = basename($book['cover_path']);

$filePath = __DIR__ . '/storage/covers/' . $fileName;

if (!is_file($filePath)) {
    http_response_code(404);
    exit;
}

// Memeriksa tipe file
$finfo = new finfo(FILEINFO_MIME_TYPE);

$mime = $finfo->file($filePath);

$allowedTypes = [
    'image/jpeg',
    'image/png',
    'image/webp'
];

if (!in_array($mime, $allowedTypes, true)) {
    http_response_code(404);
    exit;
}

// Menampilkan gambar
header("Content-Type: " . $mime);
header("X-Content-Type-Options: nosniff");

readfile($filePath);

exit;
