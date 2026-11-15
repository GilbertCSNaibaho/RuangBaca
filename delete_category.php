<?php

require_once "config/database.php";
require_once "includes/auth.php";

requireRole('admin');

// Penghapusan hanya melalui POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: categories.php");
    exit;
}

verifyCsrf();

$id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: categories.php");
    exit;
}

// Periksa apakah kategori masih digunakan buku
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM books
    WHERE category_id = ?
");

$stmt->execute([$id]);

$result = $stmt->fetch();

if ((int)$result['total'] > 0) {
    header("Location: categories.php?error=used");
    exit;
}

// Hapus kategori
$stmt = $pdo->prepare("
    DELETE FROM categories
    WHERE category_id = ?
");

$stmt->execute([$id]);

header("Location: categories.php?deleted=1");
exit;
