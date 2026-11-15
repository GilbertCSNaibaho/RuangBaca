<?php

require_once "config/database.php";
require_once "includes/auth.php";

// Pastikan pengguna sudah login
requireLogin();

$role = $_SESSION["role"] ?? "";
$userId = (int) ($_SESSION["user_id"] ?? 0);
$fullName = $_SESSION["full_name"] ?? "Pengguna";
$firstName = explode(" ", trim($fullName))[0] ?: $fullName;

if ($role === "admin") {
    // 1. Statistik Buku Digital
    $totalBooks = (int) $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
    $booksThisMonth = (int) $pdo->query("
        SELECT COUNT(*) FROM books
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
          AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ")->fetchColumn();

    // 2. Statistik Kategori Aktif
    $totalCategories = (int) $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

    // 3. Statistik Anggota Terdaftar
    $totalMembers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'member'")->fetchColumn();

    // 4. Statistik Akses Hari Ini
    $stmtToday = $pdo->query("
        SELECT
            COUNT(*) AS total_today,
            SUM(CASE WHEN activity_type = 'read' THEN 1 ELSE 0 END) AS read_today,
            SUM(CASE WHEN activity_type = 'download' THEN 1 ELSE 0 END) AS download_today
        FROM book_access_history
        WHERE DATE(accessed_at) = CURRENT_DATE()
    ");
    $todayStats = $stmtToday->fetch();
    $accessToday = (int) ($todayStats['total_today'] ?? 0);
    $readToday = (int) ($todayStats['read_today'] ?? 0);
    $downloadToday = (int) ($todayStats['download_today'] ?? 0);

    // 5. Aktivitas Akses 7 Hari Terakhir
    $dayNames = [
        1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam',
        5 => 'Jum', 6 => 'Sab', 0 => 'Min'
    ];
    $weeklyData = [];
    $maxDailyCount = 1;
    for ($i = 6; $i >= 0; $i--) {
        $dateStr = date('Y-m-d', strtotime("-$i days"));
        $dayOfWeek = (int) date('w', strtotime($dateStr));
        $weeklyData[$dateStr] = [
            'label' => $dayNames[$dayOfWeek],
            'count' => 0,
            'is_today' => ($i === 0)
        ];
    }

    $stmtWeekly = $pdo->query("
        SELECT DATE(accessed_at) AS access_date, COUNT(*) AS total
        FROM book_access_history
        WHERE accessed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 DAY)
        GROUP BY DATE(accessed_at)
    ");
    foreach ($stmtWeekly->fetchAll() as $row) {
        $d = $row['access_date'];
        if (isset($weeklyData[$d])) {
            $weeklyData[$d]['count'] = (int) $row['total'];
            if ($weeklyData[$d]['count'] > $maxDailyCount) {
                $maxDailyCount = $weeklyData[$d]['count'];
            }
        }
    }

    // 6. Aktivitas Terbaru (4 item)
    $stmtRecent = $pdo->query("
        SELECT
            h.activity_type,
            h.accessed_at,
            u.full_name,
            b.book_id,
            b.title
        FROM book_access_history h
        INNER JOIN users u ON h.user_id = u.user_id
        INNER JOIN books b ON h.book_id = b.book_id
        ORDER BY h.accessed_at DESC
        LIMIT 4
    ");
    $recentActivities = $stmtRecent->fetchAll();

    // 7. Koleksi Buku Terbaru
    $stmtLatestBooks = $pdo->query("
        SELECT
            books.book_id,
            books.title,
            books.author,
            books.publication_year,
            categories.category_name
        FROM books
        INNER JOIN categories ON books.category_id = categories.category_id
        ORDER BY books.created_at DESC
        LIMIT 5
    ");
    $latestBooks = $stmtLatestBooks->fetchAll();

} elseif ($role === "member") {
    // 1. Buku terakhir diakses oleh anggota
    $stmtLast = $pdo->prepare("
        SELECT
            b.book_id,
            b.title,
            b.author,
            b.cover_path,
            c.category_name,
            h.accessed_at,
            h.activity_type
        FROM book_access_history h
        INNER JOIN books b ON h.book_id = b.book_id
        INNER JOIN categories c ON b.category_id = c.category_id
        WHERE h.user_id = ?
        ORDER BY h.accessed_at DESC
        LIMIT 1
    ");
    $stmtLast->execute([$userId]);
    $lastReadBook = $stmtLast->fetch();

    // Jika belum pernah mengakses buku, tampilkan buku terbaru yang ada di koleksi
    $hasHistory = (bool) $lastReadBook;
    if (!$lastReadBook) {
        $stmtFallback = $pdo->query("
            SELECT
                b.book_id,
                b.title,
                b.author,
                b.cover_path,
                c.category_name
            FROM books b
            INNER JOIN categories c ON b.category_id = c.category_id
            ORDER BY b.created_at DESC
            LIMIT 1
        ");
        $lastReadBook = $stmtFallback->fetch();
    }

    // 2. Total akses koleksi anggota
    $stmtTotalAccess = $pdo->prepare("
        SELECT COUNT(*) FROM book_access_history WHERE user_id = ?
    ");
    $stmtTotalAccess->execute([$userId]);
    $memberTotalAccess = (int) $stmtTotalAccess->fetchColumn();

    // 3. Koleksi buku yang tersedia di database
    $stmtFeatured = $pdo->query("
        SELECT
            b.book_id,
            b.title,
            b.author,
            b.publisher,
            b.publication_year,
            b.cover_path,
            c.category_name
        FROM books b
        INNER JOIN categories c ON b.category_id = c.category_id
        ORDER BY b.created_at DESC
        LIMIT 8
    ");
    $featuredBooks = $stmtFeatured->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard · RuangBaca Perpustakaan Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php if ($role === "admin"): ?>

    <div class="admin-layout">
        <?php renderAdminSidebar('overview'); ?>

        <div class="admin-main">
            <?php renderAdminTopbar('RuangBaca   /   Panel Admin'); ?>

            <main class="admin-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Ringkasan perpustakaan</h1>
                        <p class="page-subtitle">Pantau koleksi, anggota, dan aktivitas akses dalam satu tempat.</p>
                    </div>
                </div>

                <!-- 4 Stat Cards -->
                <div class="stat-grid-4">
                    <a href="books.php" class="stat-card">
                        <span class="stat-label">Buku digital</span>
                        <span class="stat-value"><?= str_pad((string) $totalBooks, 2, '0', STR_PAD_LEFT) ?></span>
                        <span class="stat-note stat-note-positive">↑ <?= $booksThisMonth ?> buku bulan ini</span>
                    </a>

                    <a href="categories.php" class="stat-card">
                        <span class="stat-label">Kategori aktif</span>
                        <span class="stat-value"><?= str_pad((string) $totalCategories, 2, '0', STR_PAD_LEFT) ?></span>
                        <span class="stat-note">Semua kategori terkelola</span>
                    </a>

                    <div class="stat-card">
                        <span class="stat-label">Anggota terdaftar</span>
                        <span class="stat-value"><?= str_pad((string) $totalMembers, 2, '0', STR_PAD_LEFT) ?></span>
                        <span class="stat-note">Anggota aktif perpustakaan</span>
                    </div>

                    <div class="stat-card">
                        <span class="stat-label">Akses hari ini</span>
                        <span class="stat-value"><?= str_pad((string) $accessToday, 2, '0', STR_PAD_LEFT) ?></span>
                        <span class="stat-note"><?= $readToday ?> baca · <?= $downloadToday ?> unduh</span>
                    </div>
                </div>

                <!-- Mobile Quick Actions (Figma 05 · Mobile / Admin Overview) -->
                <div class="mobile-quick-actions-section">
                    <h2 class="section-title" style="font-size: 16px; margin-bottom: 4px;">Akses cepat</h2>
                    <div class="mobile-quick-actions-grid">
                        <a href="books.php" class="mobile-quick-card tone-blue">
                            <span class="mobile-quick-symbol">B</span>
                            <span class="mobile-quick-title">Buku</span>
                            <span class="mobile-quick-sub">Kelola koleksi</span>
                        </a>
                        <a href="categories.php" class="mobile-quick-card tone-green">
                            <span class="mobile-quick-symbol">K</span>
                            <span class="mobile-quick-title">Kategori</span>
                            <span class="mobile-quick-sub">Atur kategori</span>
                        </a>
                        <a href="catalog.php" class="mobile-quick-card tone-amber">
                            <span class="mobile-quick-symbol">A</span>
                            <span class="mobile-quick-title">Katalog</span>
                            <span class="mobile-quick-sub">Lihat koleksi</span>
                        </a>
                    </div>
                </div>

                <!-- Middle Panels: Weekly Activity & Recent Activity -->
                <div class="overview-middle-grid">
                    <div class="panel-card">
                        <div class="panel-header">
                            <div>
                                <h2 class="panel-title">Aktivitas akses</h2>
                                <p class="panel-subtitle">7 hari terakhir</p>
                            </div>
                        </div>

                        <div class="bar-chart">
                            <?php foreach ($weeklyData as $dayItem):
                                $pct = $dayItem['count'] > 0
                                    ? max(18, (int) round(($dayItem['count'] / $maxDailyCount) * 100))
                                    : 12;
                            ?>
                                <div class="bar-col" title="<?= (int) $dayItem['count'] ?> akses">
                                    <div class="bar-track">
                                        <div class="bar-fill <?= $dayItem['is_today'] ? 'accent' : '' ?>" style="height: <?= $pct ?>%;"></div>
                                    </div>
                                    <span class="bar-day"><?= e($dayItem['label']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="panel-card">
                        <div class="panel-header">
                            <h2 class="panel-title">Aktivitas terbaru</h2>
                        </div>

                        <?php if (count($recentActivities) > 0): ?>
                            <div class="activity-list">
                                <?php
                                $avatarTones = ['', 'green', 'amber', ''];
                                foreach ($recentActivities as $idx => $act):
                                    $initials = userInitials($act['full_name'], 2);
                                    $actLabel = $act['activity_type'] === 'read' ? 'Membaca' : 'Mengunduh';
                                    $timeStr = date('H:i', strtotime($act['accessed_at']));
                                ?>
                                    <div class="activity-item">
                                        <div class="activity-left">
                                            <div class="activity-avatar <?= $avatarTones[$idx % count($avatarTones)] ?>">
                                                <?= e($initials) ?>
                                            </div>
                                            <div>
                                                <div class="activity-name"><?= e($act['full_name']) ?></div>
                                                <div class="activity-detail"><?= e($actLabel . ' · ' . $act['title']) ?></div>
                                            </div>
                                        </div>
                                        <span class="activity-time"><?= e($timeStr) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="font-size:12px; color:var(--text-secondary);">Belum ada aktivitas akses buku.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Latest Books Panel -->
                <div class="data-table-wrapper">
                    <div class="panel-header" style="padding: 20px 22px 4px; margin-bottom: 0;">
                        <h2 class="panel-title">Koleksi terbaru</h2>
                        <a href="books.php" class="panel-link">Lihat semua</a>
                    </div>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>JUDUL BUKU</th>
                                <th>KATEGORI</th>
                                <th>PENULIS</th>
                                <th>TAHUN</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($latestBooks) > 0): ?>
                                <?php foreach ($latestBooks as $book): ?>
                                    <tr>
                                        <td>
                                            <a href="detail_book.php?id=<?= (int) $book['book_id'] ?>" class="table-book-title">
                                                <?= e($book['title']) ?>
                                            </a>
                                        </td>
                                        <td><?= e($book['category_name']) ?></td>
                                        <td><?= e($book['author']) ?></td>
                                        <td><?= e($book['publication_year'] ?? '-') ?></td>
                                        <td>
                                            <span class="pill pill-available">Tersedia</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">Belum ada buku yang ditambahkan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

<?php elseif ($role === "member"): ?>

    <?php renderPublicHeader('home'); ?>

    <!-- Hero Banner Member -->
    <section class="hero-banner">
        <div class="hero-banner-inner">
            <div class="hero-content">
                <p class="hero-eyebrow">SELAMAT DATANG DI RUANGBACA</p>
                <h1 class="hero-title">Selamat datang, <?= e($firstName) ?>.</h1>
                <p class="hero-description">
                    Temukan koleksi digital yang mendukung proses belajar dan eksplorasi Anda.
                </p>

                <form action="catalog.php" method="GET" class="hero-search-form">
                    <input
                        type="text"
                        name="q"
                        class="hero-search-input"
                        placeholder="Cari buku berdasarkan judul, penulis, atau topik..."
                    >
                    <button type="submit" class="hero-search-btn">Cari</button>
                </form>
            </div>
        </div>
        <div class="hero-art">
            <div class="hero-art-ring">
                <div class="hero-art-dot"></div>
            </div>
        </div>
    </section>

    <main class="page-container">
        <!-- Lanjutkan Membaca & Aktivitas Anda -->
        <div class="section-heading-row">
            <div>
                <h2 class="section-title">Lanjutkan membaca</h2>
                <p class="section-subtitle">
                    <?= $hasHistory ? 'Buku terakhir Anda akses' : 'Rekomendasi buku untuk mulai membaca' ?>
                </p>
            </div>
        </div>

        <div class="member-home-top-grid">
            <?php if ($lastReadBook): ?>
                <a href="detail_book.php?id=<?= (int) $lastReadBook['book_id'] ?>" class="continue-reading-card">
                    <div class="continue-cover-wrap">
                        <?= renderBookCover($lastReadBook) ?>
                    </div>
                    <div class="continue-body">
                        <h3 class="continue-title"><?= e($lastReadBook['title']) ?></h3>
                        <p class="continue-author"><?= e($lastReadBook['author']) ?></p>

                        <?php if ($hasHistory): ?>
                            <span class="pill pill-read">
                                Terakhir diakses <?= e(date('d M Y, H:i', strtotime($lastReadBook['accessed_at']))) ?>
                            </span>
                        <?php else: ?>
                            <span class="pill pill-available">Tersedia di katalog</span>
                        <?php endif; ?>

                        <div class="progress-row">
                            <div class="progress-label">AKSES CEPAT BACAAN</div>
                            <div class="progress-bar-wrap">
                                <div class="progress-track">
                                    <div class="progress-fill" style="width: <?= $hasHistory ? '60%' : '20%' ?>;"></div>
                                </div>
                                <span class="progress-pct"><?= $hasHistory ? 'Baca →' : 'Mulai →' ?></span>
                            </div>
                        </div>
                    </div>
                </a>
            <?php else: ?>
                <div class="panel-card" style="display:flex; align-items:center; justify-content:center;">
                    <p style="font-size:13px; color:var(--text-secondary);">Belum ada buku di katalog perpustakaan.</p>
                </div>
            <?php endif; ?>

            <div class="panel-card" style="display:flex; flex-direction:column; justify-content:space-between;">
                <div>
                    <h3 class="panel-title" style="font-size:15px;">Aktivitas Anda</h3>
                    <div class="stat-value" style="margin-top:14px;"><?= (int) $memberTotalAccess ?></div>
                    <p class="stat-note">Total akses koleksi</p>
                </div>
                <a href="history.php" class="panel-link" style="margin-top:20px;">Lihat riwayat  →</a>
            </div>
        </div>

        <!-- Koleksi Pilihan (Sesuai Buku yang Ada di Database) -->
        <div class="section-heading-row">
            <div>
                <h2 class="section-title">Koleksi pilihan</h2>
                <p class="section-subtitle">Pilihan bacaan yang tersedia di perpustakaan</p>
            </div>
            <a href="catalog.php" class="panel-link">Lihat seluruh katalog →</a>
        </div>

        <?php if (count($featuredBooks) > 0): ?>
            <div class="book-grid">
                <?php foreach ($featuredBooks as $book): ?>
                    <a href="detail_book.php?id=<?= (int) $book['book_id'] ?>" class="book-card">
                        <div class="book-card-cover-wrap">
                            <?= renderBookCover($book) ?>
                        </div>
                        <h3 class="book-card-title"><?= e($book['title']) ?></h3>
                        <p class="book-card-author"><?= e($book['author']) ?></p>
                        <span class="book-card-category"><?= e($book['category_name']) ?></span>
                        <span class="book-card-meta">
                            PDF · <?= e($book['publication_year'] ?: 'DIGITAL') ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="panel-card">
                <p style="font-size:13px; color:var(--text-secondary);">Belum ada koleksi buku yang tersedia saat ini.</p>
            </div>
        <?php endif; ?>
    </main>

<?php else: ?>

    <div class="page-container">
        <div class="alert alert-error">
            Jenis akun tidak dikenali. Silakan hubungi administrator.
        </div>
    </div>

<?php endif; ?>

<script src="assets/js/app.js"></script>
</body>
</html>
