<?php

require_once "config/database.php";
require_once "includes/auth.php";

// Pengguna harus login untuk melihat riwayat pribadi
requireLogin();

$userId = (int)$_SESSION['user_id'];
$search = trim($_GET['q'] ?? '');

// Hitung statistik riwayat pengguna
$stmtStats = $pdo->prepare("
    SELECT
        COUNT(*) AS total_access,
        SUM(CASE WHEN activity_type = 'read' THEN 1 ELSE 0 END) AS total_reads,
        SUM(CASE WHEN activity_type = 'download' THEN 1 ELSE 0 END) AS total_downloads
    FROM book_access_history
    WHERE user_id = ?
");
$stmtStats->execute([$userId]);
$stats = $stmtStats->fetch();

$totalAccess = (int)($stats['total_access'] ?? 0);
$totalReads = (int)($stats['total_reads'] ?? 0);
$totalDownloads = (int)($stats['total_downloads'] ?? 0);

// Mengambil riwayat milik pengguna yang sedang login beserta sampul buku
$sql = "
    SELECT
        book_access_history.history_id,
        book_access_history.activity_type,
        book_access_history.accessed_at,
        books.book_id,
        books.title,
        books.author,
        books.cover_path
    FROM book_access_history
    INNER JOIN books
        ON book_access_history.book_id = books.book_id
    WHERE book_access_history.user_id = ?
";

$params = [$userId];

if ($search !== '') {
    $sql .= " AND (books.title LIKE ? OR books.author LIKE ?)";
    $kw = "%" . $search . "%";
    $params[] = $kw;
    $params[] = $kw;
}

$sql .= " ORDER BY book_access_history.accessed_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$histories = $stmt->fetchAll();

// Helper format tanggal Indonesia singkat
function formatIndoDate($datetimeStr) {
    $ts = strtotime($datetimeStr);
    if (!$ts) {
        return '-';
    }
    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    $d = date('d', $ts);
    $m = $months[(int)date('n', $ts)] ?? date('M', $ts);
    $y = date('Y', $ts);
    return "$d $m $y";
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Aktivitas — RuangBaca</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php renderPublicHeader('history'); ?>

<main class="page-container">
    <div class="page-header">
        <div>
            <div class="page-eyebrow">AKUN ANGGOTA</div>
            <h1 class="page-title">Riwayat aktivitas</h1>
            <p class="page-subtitle">Catatan buku yang pernah Anda baca atau unduh menggunakan akun ini.</p>
        </div>
        <div class="page-actions">
            <a href="catalog.php" class="btn btn-secondary btn-sm">Jelajahi Katalog</a>
        </div>
    </div>

    <!-- 3 Stat Cards (Desktop & Mobile Figma 12:141) -->
    <div class="stat-grid-3">
        <div class="stat-card">
            <div class="stat-value"><?= str_pad((string)$totalAccess, 2, '0', STR_PAD_LEFT) ?></div>
            <div class="stat-label">Total akses</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= str_pad((string)$totalReads, 2, '0', STR_PAD_LEFT) ?></div>
            <div class="stat-label">Dibaca</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= str_pad((string)$totalDownloads, 2, '0', STR_PAD_LEFT) ?></div>
            <div class="stat-label">Diunduh</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-bar-left">
            <span class="panel-title">Aktivitas terbaru</span>
        </div>
        <form method="GET" action="history.php" class="filter-bar-right">
            <input
                type="text"
                name="q"
                class="filter-input"
                placeholder="Cari judul buku di riwayat..."
                value="<?= e($search) ?>"
                aria-label="Cari riwayat"
            >
            <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
            <?php if ($search !== ''): ?>
                <a href="history.php" class="btn btn-ghost btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Mobile Card View (Figma 04 · Mobile / Reading History 12:141) -->
    <?php if (count($histories) > 0): ?>
        <div class="mobile-history-list">
            <?php foreach ($histories as $idx => $history): ?>
                <a href="detail_book.php?id=<?= (int)$history['book_id'] ?>" class="mobile-history-card">
                    <?= renderMiniBookCover($history, $idx) ?>
                    <div class="mobile-history-body">
                        <div class="mobile-history-title"><?= e($history['title']) ?></div>
                        <div>
                            <?php if ($history['activity_type'] === 'read'): ?>
                                <span class="pill pill-read">Membaca</span>
                            <?php else: ?>
                                <span class="pill pill-download">Mengunduh</span>
                            <?php endif; ?>
                        </div>
                        <div class="mobile-history-meta">
                            <span><?= e(formatIndoDate($history['accessed_at'])) ?> · <?= e(date('H.i', strtotime($history['accessed_at']))) ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Desktop Table View -->
    <div class="data-table-wrapper history-desktop-table">
        <table class="data-table">
            <thead>
                <tr>
                    <th>BUKU</th>
                    <th>AKTIVITAS</th>
                    <th>TANGGAL</th>
                    <th>WAKTU</th>
                    <th>AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($histories) > 0): ?>
                    <?php foreach ($histories as $idx => $history): ?>
                        <tr>
                            <td>
                                <div class="table-book-cell">
                                    <?= renderMiniBookCover($history, $idx) ?>
                                    <div>
                                        <a href="detail_book.php?id=<?= (int)$history['book_id'] ?>" class="table-book-title">
                                            <?= e($history['title']) ?>
                                        </a>
                                        <div class="table-book-sub"><?= e($history['author']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($history['activity_type'] === 'read'): ?>
                                    <span class="pill pill-read">Membaca</span>
                                <?php else: ?>
                                    <span class="pill pill-download">Mengunduh</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(formatIndoDate($history['accessed_at'])) ?></td>
                            <td><?= e(date('H:i', strtotime($history['accessed_at']))) ?> WIB</td>
                            <td>
                                <a href="detail_book.php?id=<?= (int)$history['book_id'] ?>" class="action-link">
                                    Lihat buku
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <div class="empty-state-title">Belum ada riwayat aktivitas</div>
                                <p>Buku yang Anda baca atau unduh akan tercatat secara otomatis di halaman ini.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (count($histories) === 0): ?>
        <div class="panel-card mobile-history-list">
            <div class="empty-state">
                <div class="empty-state-title">Belum ada riwayat aktivitas</div>
                <p>Buku yang Anda baca atau unduh akan tercatat secara otomatis di halaman ini.</p>
            </div>
        </div>
    <?php endif; ?>
</main>

<script src="assets/js/app.js"></script>
</body>
</html>
