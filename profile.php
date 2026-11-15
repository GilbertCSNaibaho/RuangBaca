<?php

require_once "config/database.php";
require_once "includes/auth.php";

requireLogin();

$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT *
    FROM users
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: logout.php");
    exit;
}

$errors = [];
$successMessage = "";

$fullName = $user['full_name'];
$email = $user['email'];
$roleLabel = ($user['role'] === 'admin') ? 'Administrator Perpustakaan' : 'Anggota Perpustakaan';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    verifyCsrf();

    $newFullName = trim($_POST['full_name'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newFullName === '') {
        $errors[] = "Nama lengkap tidak boleh kosong.";
    } elseif (strLength($newFullName) > 100) {
        $errors[] = "Nama lengkap maksimal 100 karakter.";
    }

    $wantsPasswordChange = ($currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '');

    if ($wantsPasswordChange) {
        if ($currentPassword === '') {
            $errors[] = "Masukkan kata sandi saat ini untuk mengubah kata sandi.";
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $errors[] = "Kata sandi saat ini tidak sesuai.";
        }

        if (strlen($newPassword) < 8) {
            $errors[] = "Kata sandi baru minimal 8 karakter.";
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = "Konfirmasi kata sandi baru tidak cocok.";
        }
    }

    if (empty($errors)) {
        if ($wantsPasswordChange) {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmtUpdate = $pdo->prepare("
                UPDATE users
                SET full_name = ?, password = ?
                WHERE user_id = ?
            ");
            $stmtUpdate->execute([$newFullName, $hashed, $userId]);
        } else {
            $stmtUpdate = $pdo->prepare("
                UPDATE users
                SET full_name = ?
                WHERE user_id = ?
            ");
            $stmtUpdate->execute([$newFullName, $userId]);
        }

        $_SESSION['full_name'] = $newFullName;
        $fullName = $newFullName;
        $user['full_name'] = $newFullName;
        $successMessage = "Pengaturan profil berhasil diperbarui.";
    }
}

$initials = userInitials($fullName);

$createdAtFormatted = "Terdaftar di RuangBaca";
if (!empty($user['created_at'])) {
    $ts = strtotime($user['created_at']);
    if ($ts) {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $createdAtFormatted = date('d', $ts) . ' ' . ($months[(int)date('n', $ts)] ?? date('M', $ts)) . ' ' . date('Y', $ts);
    }
}

$memberIdCode = sprintf("RB-%s-%04d", date('Y'), $userId);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil &amp; Pengaturan Akun — RuangBaca</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="public-page">
    <?php renderPublicHeader('profile'); ?>

    <main class="public-container">
        <div class="page-head">
            <div>
                <div class="page-eyebrow">AKUN ANGGOTA</div>
                <h1 class="page-title">Profil &amp; pengaturan akun</h1>
                <p class="page-subtitle">Kelola informasi pribadi, alamat email, dan keamanan kata sandi akun Anda.</p>
            </div>
        </div>

        <?php if ($successMessage !== ''): ?>
            <div class="alert alert-success"><?= e($successMessage) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin: 0; padding-left: 18px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="profile-grid">
            <!-- Left Column: Account Summary Card -->
            <div class="panel-card profile-card">
                <div class="profile-avatar-lg"><?= e($initials) ?></div>
                <div class="profile-name"><?= e($fullName) ?></div>
                <div class="profile-email"><?= e($email) ?></div>
                <div>
                    <span class="pill pill-info"><?= e($roleLabel) ?></span>
                </div>

                <div class="profile-meta-list">
                    <div>
                        <div class="profile-meta-label">Terdaftar sejak</div>
                        <div class="profile-meta-val"><?= e($createdAtFormatted) ?></div>
                    </div>
                    <div>
                        <div class="profile-meta-label">ID Keanggotaan</div>
                        <div class="profile-meta-val"><?= e($memberIdCode) ?></div>
                    </div>
                    <div>
                        <div class="profile-meta-label">Status Akun</div>
                        <div class="profile-meta-val" style="color: #1F7042;">Aktif · Terverifikasi</div>
                    </div>
                </div>

                <form action="logout.php" method="POST" style="width: 100%; margin-top: 24px;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <button type="submit" class="btn btn-danger-outline btn-block">Keluar dari akun</button>
                </form>
            </div>

            <!-- Right Column: Profile & Password Form -->
            <div class="panel-card">
                <div class="panel-card-header">
                    <h2 class="panel-card-title">Informasi pribadi</h2>
                </div>
                <div class="panel-card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

                        <div class="form-row-2">
                            <div class="form-group">
                                <label class="form-label" for="full_name">Nama lengkap</label>
                                <input
                                    type="text"
                                    id="full_name"
                                    name="full_name"
                                    class="form-control"
                                    value="<?= e($fullName) ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email_readonly">Alamat email</label>
                                <input
                                    type="email"
                                    id="email_readonly"
                                    class="form-control"
                                    value="<?= e($email) ?>"
                                    disabled
                                    style="background: #F6F7F8; color: #738296;"
                                >
                            </div>
                        </div>

                        <div style="border-top: 1px solid #DFE5EC; margin: 16px 0 20px; padding-top: 20px;">
                            <h3 class="panel-card-title" style="margin-bottom: 6px;">Ubah kata sandi</h3>
                            <p style="font-size: 12px; color: #738296; margin: 0 0 16px;">
                                Kosongkan bagian ini apabila Anda tidak ingin mengubah kata sandi.
                            </p>

                            <div class="form-group">
                                <label class="form-label" for="current_password">Kata sandi saat ini</label>
                                <input
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    class="form-control"
                                    placeholder="••••••••"
                                >
                            </div>

                            <div class="form-row-2">
                                <div class="form-group">
                                    <label class="form-label" for="new_password">Kata sandi baru</label>
                                    <input
                                        type="password"
                                        id="new_password"
                                        name="new_password"
                                        class="form-control"
                                        placeholder="Minimal 8 karakter"
                                    >
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="confirm_password">Konfirmasi kata sandi baru</label>
                                    <input
                                        type="password"
                                        id="confirm_password"
                                        name="confirm_password"
                                        class="form-control"
                                        placeholder="Ulangi kata sandi baru"
                                    >
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px;">
                            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                            <a href="dashboard.php" class="btn btn-outline">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>
