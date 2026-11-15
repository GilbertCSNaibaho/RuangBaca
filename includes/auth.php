<?php

// Memulai session dengan konfigurasi cookie yang aman jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ||
        ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) ||
        (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
    );

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// Mengamankan output HTML dari XSS
function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Menghitung panjang karakter string UTF-8 dengan aman
function strLength($value) {
    $value = (string) $value;
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }
    return strlen($value);
}

// Memeriksa apakah pengguna sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Memaksa pengguna untuk login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

// Memeriksa hak akses pengguna berdasarkan role
function requireRole($role) {
    requireLogin();

    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        exit("Anda tidak memiliki hak akses.");
    }
}

// Membuat token keamanan formulir (CSRF)
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION['csrf_token'];
}

// Memeriksa token formulir (CSRF)
function verifyCsrf() {
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'] ?? '',
            $_POST['csrf_token']
        )
    ) {
        http_response_code(403);
        exit("Permintaan tidak valid. Silakan muat ulang halaman.");
    }
}

// Mengambil inisial nama (1 atau 2 huruf)
function userInitials($name, $length = 1) {
    $name = trim((string) $name);
    if ($name === '') {
        return 'R';
    }
    $parts = preg_split('/\s+/', $name);
    if ($length >= 2 && count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
    }
    return strtoupper(substr($name, 0, $length));
}

// Merender sampul buku (menggunakan gambar sampul asli jika tersedia, atau sampul editorial RuangBaca sesuai buku yang ada)
function renderBookCover(array $book) {
    $bookId = (int) ($book['book_id'] ?? 1);
    $title = (string) ($book['title'] ?? 'Buku Digital');
    $coverPath = trim((string) ($book['cover_path'] ?? ''));

    if ($coverPath !== '') {
        return '<img src="cover.php?id=' . $bookId . '" alt="Sampul ' . e($title) . '" class="book-cover-img" loading="lazy">';
    }

    $tones = ['', 'tone-1', 'tone-2', 'tone-3', 'tone-4'];
    $toneClass = $tones[$bookId % count($tones)];
    $imprintNo = str_pad((string) ($bookId % 99 ?: 1), 2, '0', STR_PAD_LEFT);

    return '
        <div class="editorial-cover ' . $toneClass . '">
            <div class="editorial-cover-stripe"></div>
            <span class="editorial-cover-imprint">RUANGBACA / ' . e($imprintNo) . '</span>
            <div class="editorial-cover-motif"></div>
            <div class="editorial-cover-title">' . e($title) . '</div>
        </div>
    ';
}

// Merender mini cover pada tabel manajemen buku dan kartu riwayat
function renderMiniBookCover(array $book, int $index = 0) {
    $bookId = (int) ($book['book_id'] ?? 1);
    $title = (string) ($book['title'] ?? 'Buku');
    $coverPath = trim((string) ($book['cover_path'] ?? ''));

    if ($coverPath !== '') {
        return '<img src="cover.php?id=' . $bookId . '" alt="Sampul ' . e($title) . '" class="mini-cover-img" loading="lazy">';
    }

    $words = preg_split('/\s+/', trim($title));
    $abbr = '';
    foreach ($words as $w) {
        if ($w !== '' && strlen($abbr) < 3) {
            $abbr .= strtoupper(substr($w, 0, 1));
        }
    }
    if ($abbr === '') {
        $abbr = 'BK';
    }
    $tones = ['', 'tone-1', 'tone-2', 'tone-3', 'tone-4'];
    $toneClass = $tones[$index % count($tones)];

    return '<div class="mini-cover-badge ' . $toneClass . '">' . e($abbr) . '</div>';
}

// Merender Sidebar Admin (Desktop) + Drawer Mobile + Bottom Navigation Admin (Mobile) sesuai Figma
function renderAdminSidebar(string $activeMenu = 'overview') {
    $fullName = $_SESSION['full_name'] ?? 'Admin';
    $email = $_SESSION['email'] ?? 'admin@ruangbaca.id';
    $initial = userInitials($fullName, 1);
    ?>
    <!-- Overlay Drawer Mobile -->
    <div class="mobile-drawer-backdrop" id="mobile-drawer-backdrop"></div>

    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-sidebar-top">
            <div class="sidebar-brand-row">
                <a href="dashboard.php" class="brand-lockup brand-lockup-dark">
                    <div class="brand-mark">R</div>
                    <div class="brand-text">
                        <span class="brand-name">RuangBaca</span>
                        <span class="brand-descriptor">PERPUSTAKAAN DIGITAL</span>
                    </div>
                </a>
                <button type="button" class="mobile-drawer-close" id="mobile-drawer-close" aria-label="Tutup navigasi">&times;</button>
            </div>

            <div class="sidebar-section-label">MENU UTAMA</div>
            <nav class="sidebar-nav" aria-label="Menu Utama Admin">
                <a href="dashboard.php" class="sidebar-link <?= $activeMenu === 'overview' ? 'active' : '' ?>">
                    <span class="sidebar-marker">Overview</span>
                    <span>Ringkasan</span>
                </a>
                <a href="books.php" class="sidebar-link <?= $activeMenu === 'books' ? 'active' : '' ?>">
                    <span class="sidebar-marker">Books</span>
                    <span>Manajemen Buku</span>
                </a>
                <a href="categories.php" class="sidebar-link <?= $activeMenu === 'categories' ? 'active' : '' ?>">
                    <span class="sidebar-marker">Tags</span>
                    <span>Kategori</span>
                </a>
                <a href="catalog.php" class="sidebar-link <?= $activeMenu === 'catalog' ? 'active' : '' ?>">
                    <span class="sidebar-marker">View</span>
                    <span>Lihat Katalog</span>
                </a>
            </nav>

            <hr class="sidebar-rule">

            <div class="sidebar-section-label">AKUN &amp; BANTUAN</div>
            <nav class="sidebar-nav" aria-label="Menu Akun Admin">
                <a href="profile.php" class="sidebar-link <?= $activeMenu === 'profile' ? 'active' : '' ?>">
                    <span class="sidebar-marker">Info</span>
                    <span>Profil &amp; Pengaturan</span>
                </a>
            </nav>
        </div>

        <div class="sidebar-footer">
            <a href="profile.php" class="sidebar-user">
                <div class="sidebar-avatar"><?= e($initial) ?></div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= e($fullName) ?></div>
                    <div class="sidebar-user-email"><?= e($email) ?></div>
                </div>
            </a>
            <form action="logout.php" method="POST" style="margin:0;">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <button type="submit" class="sidebar-logout-btn" title="Keluar dari akun">Keluar</button>
            </form>
        </div>
    </aside>

    <!-- Bottom Navigation khusus Mobile Admin (Figma 05 · Mobile / Admin Overview) -->
    <nav class="mobile-bottom-nav" aria-label="Navigasi Bawah Admin">
        <a href="dashboard.php" class="mobile-bottom-link <?= $activeMenu === 'overview' ? 'active' : '' ?>">
            <span class="mobile-bottom-icon">⌂</span>
            <span class="mobile-bottom-label">Ringkasan</span>
        </a>
        <a href="books.php" class="mobile-bottom-link <?= $activeMenu === 'books' ? 'active' : '' ?>">
            <span class="mobile-bottom-icon">▦</span>
            <span class="mobile-bottom-label">Buku</span>
        </a>
        <a href="categories.php" class="mobile-bottom-link <?= $activeMenu === 'categories' ? 'active' : '' ?>">
            <span class="sidebar-bottom-symbol mobile-bottom-icon">≡</span>
            <span class="mobile-bottom-label">Kategori</span>
        </a>
        <a href="profile.php" class="mobile-bottom-link <?= $activeMenu === 'profile' ? 'active' : '' ?>">
            <span class="mobile-bottom-icon">○</span>
            <span class="mobile-bottom-label">Akun</span>
        </a>
    </nav>
    <?php
}

// Merender Topbar Admin (Desktop) & Header Gelap Admin (Mobile sesuai Figma 05 · Mobile / Admin Overview)
function renderAdminTopbar(string $breadcrumb = 'RuangBaca   /   Panel Admin', string $searchValue = '') {
    $fullName = $_SESSION['full_name'] ?? 'Administrator';
    $initials = userInitials($fullName, 2);
    $singleInitial = userInitials($fullName, 1);
    ?>
    <header class="admin-topbar">
        <!-- Tampilan Mobile Admin Header (Figma 12:197) -->
        <div class="admin-mobile-bar">
            <div class="admin-mobile-left">
                <button type="button" class="mobile-menu-trigger admin-mobile-trigger" id="mobile-drawer-open" aria-label="Buka menu navigasi">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <a href="dashboard.php" class="brand-lockup brand-lockup-dark">
                    <div class="brand-mark brand-mark-sm">R</div>
                    <div class="brand-text">
                        <span class="brand-name">RuangBaca</span>
                        <span class="brand-descriptor">PANEL ADMIN</span>
                    </div>
                </a>
            </div>
            <a href="profile.php" class="admin-mobile-avatar" title="<?= e($fullName) ?>">
                <?= e($singleInitial) ?>
            </a>
        </div>

        <!-- Tampilan Desktop Admin Topbar -->
        <div class="topbar-breadcrumb"><?= e($breadcrumb) ?></div>
        <div class="topbar-right">
            <form action="books.php" method="GET" class="topbar-search">
                <input type="text" name="q" placeholder="Cari judul atau penulis buku..." value="<?= e($searchValue) ?>" aria-label="Cari buku">
            </form>
            <a href="profile.php" class="topbar-profile">
                <div class="topbar-avatar"><?= e($initials) ?></div>
                <div class="topbar-user-meta">
                    <div class="topbar-user-name"><?= e($fullName) ?></div>
                    <div class="topbar-user-role">Pengelola perpustakaan</div>
                </div>
            </a>
        </div>
    </header>
    <?php
}

// Merender Header Publik / Member + Bottom Navigation Mobile sesuai Figma
function renderPublicHeader(string $activeNav = 'catalog') {
    $loggedIn = isLoggedIn();
    $role = $_SESSION['role'] ?? '';
    $fullName = $_SESSION['full_name'] ?? 'Anggota';
    $firstName = explode(' ', trim($fullName))[0] ?: 'Anggota';
    $initial = userInitials($fullName, 1);
    ?>
    <header class="public-header">
        <div class="public-header-inner">
            <a href="<?= $loggedIn ? 'dashboard.php' : 'catalog.php' ?>" class="brand-lockup">
                <div class="brand-mark">R</div>
                <div class="brand-text">
                    <span class="brand-name">RuangBaca</span>
                    <span class="brand-descriptor">PERPUSTAKAAN DIGITAL</span>
                </div>
            </a>

            <nav class="public-nav" aria-label="Navigasi Utama">
                <?php if ($loggedIn): ?>
                    <a href="dashboard.php" class="public-nav-link <?= $activeNav === 'home' ? 'active' : '' ?>">Beranda</a>
                    <a href="catalog.php" class="public-nav-link <?= $activeNav === 'catalog' ? 'active' : '' ?>">Katalog</a>
                    <?php if ($role === 'admin'): ?>
                        <a href="books.php" class="public-nav-link <?= $activeNav === 'books' ? 'active' : '' ?>">Panel Admin</a>
                    <?php endif; ?>
                    <a href="history.php" class="public-nav-link <?= $activeNav === 'history' ? 'active' : '' ?>">Riwayat</a>
                    <a href="profile.php" class="public-nav-link <?= $activeNav === 'profile' ? 'active' : '' ?>">Profil</a>
                <?php else: ?>
                    <a href="catalog.php" class="public-nav-link <?= $activeNav === 'catalog' ? 'active' : '' ?>">Katalog</a>
                    <a href="login.php" class="public-nav-link">Masuk</a>
                    <a href="register.php" class="public-nav-link">Daftar Anggota</a>
                <?php endif; ?>
            </nav>

            <div class="public-header-actions">
                <?php if ($loggedIn): ?>
                    <a href="profile.php" class="member-chip" title="Profil <?= e($fullName) ?>">
                        <div class="member-chip-avatar"><?= e($initial) ?></div>
                        <span class="member-chip-name"><?= e($firstName) ?></span>
                    </a>
                    <form action="logout.php" method="POST" class="header-logout-form" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <button type="submit" class="btn btn-secondary btn-sm">Logout</button>
                    </form>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary btn-sm">Masuk</a>
                    <a href="register.php" class="btn btn-primary btn-sm">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Bottom Navigation khusus Mobile Member/Publik (Figma 01 · Mobile / Catalogue & 04 · Mobile / Reading History) -->
    <nav class="mobile-bottom-nav" aria-label="Navigasi Bawah">
        <?php if ($loggedIn): ?>
            <a href="dashboard.php" class="mobile-bottom-link <?= $activeNav === 'home' ? 'active' : '' ?>">
                <span class="mobile-bottom-icon">⌂</span>
                <span class="mobile-bottom-label">Beranda</span>
            </a>
            <a href="catalog.php" class="mobile-bottom-link <?= $activeNav === 'catalog' ? 'active' : '' ?>">
                <span class="mobile-bottom-icon">▦</span>
                <span class="mobile-bottom-label">Katalog</span>
            </a>
            <a href="history.php" class="mobile-bottom-link <?= $activeNav === 'history' ? 'active' : '' ?>">
                <span class="mobile-bottom-icon">◷</span>
                <span class="mobile-bottom-label">Riwayat</span>
            </a>
            <a href="profile.php" class="mobile-bottom-link <?= $activeNav === 'profile' ? 'active' : '' ?>">
                <span class="mobile-bottom-icon">○</span>
                <span class="mobile-bottom-label">Akun</span>
            </a>
        <?php else: ?>
            <a href="catalog.php" class="mobile-bottom-link <?= $activeNav === 'catalog' ? 'active' : '' ?>">
                <span class="mobile-bottom-icon">▦</span>
                <span class="mobile-bottom-label">Katalog</span>
            </a>
            <a href="login.php" class="mobile-bottom-link">
                <span class="mobile-bottom-icon">→</span>
                <span class="mobile-bottom-label">Masuk</span>
            </a>
            <a href="register.php" class="mobile-bottom-link">
                <span class="mobile-bottom-icon">+</span>
                <span class="mobile-bottom-label">Daftar</span>
            </a>
        <?php endif; ?>
    </nav>
    <?php
}
