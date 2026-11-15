<?php
/**
 * Contoh Konfigurasi Koneksi Database PDO MySQL — RuangBaca
 *
 * Cara Penggunaan setelah Clone Repository:
 * 1. Salin (copy) file ini menjadi `config/database.php`
 *    Perintah terminal: copy config\database.example.php config\database.php
 * 2. Sesuaikan nilai $host, $dbname, $username, dan $password di bawah ini
 *    dengan kredensial MySQL/MariaDB lokal (XAMPP) atau server hosting Anda.
 */

$host = "localhost";                 // Contoh hosting: sqlXXX.infinityfree.com
$dbname = "perpustakaan_digital";    // Nama database MySQL
$username = "your_db_username";      // Default XAMPP: "root"
$password = "your_db_password";      // Default XAMPP: "" (kosong)

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

    $pdo->setAttribute(
        PDO::ATTR_EMULATE_PREPARES,
        false
    );

} catch (PDOException $e) {

    error_log("Database connection error: " . $e->getMessage());
    http_response_code(500);
    exit("Terjadi gangguan pada layanan sistem. Silakan coba lagi beberapa saat lagi.");

}
