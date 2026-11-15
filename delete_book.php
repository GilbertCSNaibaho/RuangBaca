<?php

require_once "config/database.php";
require_once "includes/auth.php";

// Hanya Admin yang boleh menghapus buku
requireRole('admin');

// Penghapusan harus menggunakan POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: books.php");
    exit;
}

// Periksa keamanan formulir
verifyCsrf();

// Ambil ID buku
$id = filter_input(
    INPUT_POST,
    'book_id',
    FILTER_VALIDATE_INT
);

if (!$id) {
    header("Location: books.php");
    exit;
}

try {
    // 1. Mulai transaksi database
    $pdo->beginTransaction();

    // Ambil lokasi file dan kunci baris buku selama transaksi
    $stmt = $pdo->prepare("
        SELECT pdf_path, cover_path
        FROM books
        WHERE book_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$id]);
    $book = $stmt->fetch();

    if (!$book) {
        $pdo->rollBack();
        header("Location: books.php?error=notfound");
        exit;
    }

    // 2. Periksa keberadaan riwayat aktivitas buku (Kebijakan: RESTRICT)
    $historyStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM book_access_history
        WHERE book_id = ?
    ");
    $historyStmt->execute([$id]);
    $historyCount = (int) $historyStmt->fetchColumn();

    // 3. Jika riwayat ditemukan, batalkan transaksi dan tolak penghapusan
    if ($historyCount > 0) {
        $pdo->rollBack();
        header("Location: books.php?error=used_in_history");
        exit;
    }

    // 4. Jika tidak ada riwayat, hapus record buku
    $deleteStmt = $pdo->prepare("
        DELETE FROM books
        WHERE book_id = ?
    ");
    $deleteStmt->execute([$id]);

    // 5. Commit transaksi database sebelum menghapus file fisik
    $pdo->commit();

    // 6. Setelah database berhasil diperbarui, hapus file PDF dan sampul dengan validasi path
    $pdfPath = trim((string) ($book['pdf_path'] ?? ''));
    if ($pdfPath !== '' && strpos($pdfPath, 'storage/books/') === 0) {
        $booksStorageDir = realpath(__DIR__ . '/storage/books');
        $pdfCandidate = __DIR__ . '/storage/books/' . basename($pdfPath);
        $resolvedPdf = realpath($pdfCandidate);

        if (
            $booksStorageDir !== false &&
            $resolvedPdf !== false &&
            dirname($resolvedPdf) === $booksStorageDir &&
            is_file($resolvedPdf)
        ) {
            unlink($resolvedPdf);
        }
    }

    $coverPath = trim((string) ($book['cover_path'] ?? ''));
    if ($coverPath !== '' && strpos($coverPath, 'storage/covers/') === 0) {
        $coversStorageDir = realpath(__DIR__ . '/storage/covers');
        $coverCandidate = __DIR__ . '/storage/covers/' . basename($coverPath);
        $resolvedCover = realpath($coverCandidate);

        if (
            $coversStorageDir !== false &&
            $resolvedCover !== false &&
            dirname($resolvedCover) === $coversStorageDir &&
            is_file($resolvedCover)
        ) {
            unlink($resolvedCover);
        }
    }

    header("Location: books.php?deleted=1");
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    header("Location: books.php?error=delete_failed");
    exit;
}
