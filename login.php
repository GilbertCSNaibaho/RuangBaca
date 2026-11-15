<?php

require_once "config/database.php";
require_once "includes/auth.php";

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verifyCsrf();

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $pdo->prepare(
        "SELECT user_id, full_name, email, password, role
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch();

    // Periksa email dan password
    if ($user && password_verify($password, $user["password"])) {

        // Regenerasi session ID setelah login
        session_regenerate_id(true);

        // Simpan identitas pengguna ke session
        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["full_name"] = $user["full_name"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["role"] = $user["role"];

        // Perbarui token keamanan
        unset($_SESSION["csrf_token"]);

        header("Location: dashboard.php");
        exit;

    } else {

        $error = "Email atau password salah.";

    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk · RuangBaca Perpustakaan Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="auth-layout">
        <!-- Panel Kiri (Brand & Ilustrasi) -->
        <div class="auth-left">
            <div>
                <a href="catalog.php" class="brand-lockup brand-lockup-dark">
                    <div class="brand-mark">R</div>
                    <div class="brand-text">
                        <span class="brand-name">RuangBaca</span>
                        <span class="brand-descriptor">PERPUSTAKAAN DIGITAL</span>
                    </div>
                </a>

                <p class="auth-eyebrow">RUANG BACA UNTUK SEMUA</p>
                <h1 class="auth-headline">Temukan ruang untuk bertumbuh.</h1>
                <p class="auth-description">
                    Akses koleksi digital yang terkurasi untuk belajar, mengeksplorasi, dan menemukan gagasan baru.
                </p>

                <div class="auth-illustration">
                    <div class="auth-spine auth-spine-idea">IDEA</div>
                    <div class="auth-spine auth-spine-code">CODE</div>
                    <div class="auth-spine auth-spine-notes">NOTES</div>
                </div>
            </div>

            <p class="auth-footer-note">Baca satu halaman. Temukan banyak kemungkinan.</p>
        </div>

        <!-- Panel Kanan (Form Login) -->
        <div class="auth-right">
            <div class="auth-form-box">
                <h2 class="auth-title">Selamat datang kembali</h2>
                <p class="auth-subtitle">Masuk untuk melanjutkan aktivitas membaca Anda.</p>

                <?php if (isset($_GET["registered"])): ?>
                    <div class="alert alert-success">
                        Registrasi berhasil. Silakan masuk menggunakan akun Anda.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET["logout"])): ?>
                    <div class="alert alert-success">
                        Anda berhasil logout dari sesi perpustakaan.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET["forgot"])): ?>
                    <div class="alert alert-success">
                        Silakan hubungi administrator perpustakaan untuk melakukan pengaturan ulang kata sandi akun Anda.
                    </div>
                <?php endif; ?>

                <?php if ($error !== ""): ?>
                    <div class="alert alert-error">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                    <div class="form-group">
                        <label class="form-label" for="email">EMAIL</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="nama@email.com"
                            value="<?= e($_POST['email'] ?? '') ?>"
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
                            placeholder="••••••••••••"
                            required
                        >
                    </div>

                    <div class="auth-forgot-row">
                        <a href="login.php?forgot=1" class="auth-forgot-link">Lupa password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Masuk ke akun
                    </button>
                </form>

                <p class="auth-switch">
                    Belum memiliki akun?
                    <a href="register.php">Daftar sebagai anggota</a>
                </p>

                <hr class="auth-divider">

                <p class="auth-help">
                    Akses khusus perpustakaan digital kampus · <a href="catalog.php">Lihat katalog publik</a>
                </p>
            </div>
        </div>
    </div>

</body>
</html>
