<?php

require_once "config/database.php";
require_once "includes/auth.php";

// Jika sudah login, arahkan ke dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];

$full_name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verifyCsrf();

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Validasi nama
    if ($full_name === "") {
        $errors[] = "Nama lengkap wajib diisi.";
    }

    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }

    // Validasi password
    if (strlen($password) < 8) {
        $errors[] = "Password minimal 8 karakter.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak sama.";
    }

    // Periksa email yang sudah digunakan
    if (empty($errors)) {

        $check = $pdo->prepare(
            "SELECT user_id FROM users WHERE email = ?"
        );

        $check->execute([$email]);

        if ($check->fetch()) {
            $errors[] = "Email sudah terdaftar.";
        }
    }

    // Simpan akun baru
    if (empty($errors)) {

        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $pdo->prepare(
            "INSERT INTO users
            (full_name, email, password, role)
            VALUES (?, ?, ?, 'member')"
        );

        $stmt->execute([
            $full_name,
            $email,
            $hashed_password
        ]);

        header("Location: login.php?registered=1");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Anggota · RuangBaca Perpustakaan Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="auth-layout">
        <!-- Panel Kiri -->
        <div class="auth-left">
            <div>
                <a href="catalog.php" class="brand-lockup brand-lockup-dark">
                    <div class="brand-mark">R</div>
                    <div class="brand-text">
                        <span class="brand-name">RuangBaca</span>
                        <span class="brand-descriptor">PERPUSTAKAAN DIGITAL</span>
                    </div>
                </a>

                <p class="auth-eyebrow">MULAI DARI SINI</p>
                <h1 class="auth-headline">Satu akun. Banyak pengetahuan.</h1>
                <p class="auth-description">
                    Buat akun anggota untuk mengakses koleksi, membaca buku digital, dan menyimpan riwayat bacaan Anda.
                </p>

                <div class="auth-quote-panel">
                    <p class="auth-quote-text">
                        “Membaca adalah cara paling sederhana untuk memperluas dunia.”
                    </p>
                    <p class="auth-quote-attr">RUANGBACA  /  PERPUSTAKAAN DIGITAL</p>
                </div>
            </div>

            <p class="auth-footer-note">Perpustakaan Digital RuangBaca</p>
        </div>

        <!-- Panel Kanan (Form Registrasi) -->
        <div class="auth-right">
            <div class="auth-form-box">
                <h2 class="auth-title">Buat akun anggota</h2>
                <p class="auth-subtitle">Isi informasi berikut untuk mulai menggunakan perpustakaan.</p>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error">
                        <?= e($error) ?>
                    </div>
                <?php endforeach; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                    <div class="form-group">
                        <label class="form-label" for="full_name">NAMA LENGKAP</label>
                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            class="form-control"
                            placeholder="Nama lengkap Anda"
                            value="<?= e($full_name) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">EMAIL</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="nama@email.com"
                            value="<?= e($email) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">PASSWORD</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Minimal 8 karakter"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">KONFIRMASI PASSWORD</label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            placeholder="Ulangi password Anda"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">
                        Buat akun anggota
                    </button>
                </form>

                <p class="auth-help" style="margin-top:16px;">
                    Dengan mendaftar, Anda menyetujui ketentuan penggunaan perpustakaan digital.
                </p>

                <p class="auth-switch">
                    Sudah punya akun?
                    <a href="login.php">Masuk di sini</a>
                </p>
            </div>
        </div>
    </div>

</body>
</html>
