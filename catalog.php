<?php

require_once "config/database.php";
require_once "includes/auth.php";

// Mengambil input pencarian, kategori, dan pengurutan
$search = trim($_GET['q'] ?? '');
$categoryId = $_GET['category_id'] ?? '';
$sort = $_GET['sort'] ?? 'terbaru';

if ($categoryId !== '' && !ctype_digit((string) $categoryId)) {
    $categoryId = '';
}

$allowedSorts = ['terbaru', 'judul', 'tahun'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'terbaru';
}

// Mengambil daftar kategori
$stmt = $pdo->query("
    SELECT category_id, category_name
    FROM categories
    ORDER BY category_name ASC
");

$categories = $stmt->fetchAll();

// Menyiapkan query katalog dari buku yang ada di database
$sql = "
    SELECT
        books.book_id,
        books.title,
        books.author,
        books.publisher,
        books.publication_year,
        books.description,
        books.cover_path,
        books.category_id,
        categories.category_name
    FROM books
    INNER JOIN categories
        ON books.category_id = categories.category_id
    WHERE 1=1
";

$params = [];

// Pencarian berdasarkan judul, penulis, atau penerbit
if ($search !== '') {
    $sql .= "
        AND (
            books.title LIKE ?
            OR books.author LIKE ?
            OR books.publisher LIKE ?
        )
    ";
    $keyword = "%" . $search . "%";
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
}

// Filter kategori
if ($categoryId !== '') {
    $sql .= " AND books.category_id = ?";
    $params[] = (int) $categoryId;
}

// Pengurutan koleksi
if ($sort === 'judul') {
    $sql .= " ORDER BY books.title ASC";
} elseif ($sort === 'tahun') {
    $sql .= " ORDER BY books.publication_year DESC, books.created_at DESC";
} else {
    $sql .= " ORDER BY books.created_at DESC, books.book_id DESC";
}

// Jalankan query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$books = $stmt->fetchAll();

// Helper untuk membuat URL filter kategori sambil mempertahankan pencarian & urutan
function buildCatalogUrl($catId, $q, $sortVal) {
    $query = [];
    if ($catId !== '' && $catId !== null) {
        $query['category_id'] = $catId;
    }
    if ($q !== '') {
        $query['q'] = $q;
    }
    if ($sortVal !== '' && $sortVal !== 'terbaru') {
        $query['sort'] = $sortVal;
    }
    return 'catalog.php' . (!empty($query) ? '?' . http_build_query($query) : '');
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Buku Digital — RuangBaca</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php renderPublicHeader('catalog'); ?>

<!-- Hero Banner -->
<section class="hero-banner">
    <div class="hero-banner-inner">
        <div class="hero-content">
            <div class="hero-eyebrow">KOLEKSI DIGITAL / RUANGBACA</div>
            <h1 class="hero-title">Ilmu baru, satu halaman lebih dekat.</h1>
            <p class="hero-description">
                Telusuri koleksi buku akademik, teknologi, dan referensi pilihan. Baca langsung di peramban atau unduh untuk belajar mandiri.
            </p>

            <form method="GET" action="catalog.php" class="hero-search-form">
                <?php if ($categoryId !== ''): ?>
                    <input type="hidden" name="category_id" value="<?= e((string)$categoryId) ?>">
                <?php endif; ?>
                <?php if ($sort !== 'terbaru'): ?>
                    <input type="hidden" name="sort" value="<?= e($sort) ?>">
                <?php endif; ?>
                <input
                    type="text"
                    name="q"
                    class="hero-search-input"
                    placeholder="Cari judul, penulis, atau topik..."
                    value="<?= e($search) ?>"
                    aria-label="Cari buku"
                >
                <button type="submit" class="hero-search-btn">Cari Buku</button>
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
    <!-- Category Pills & Sort Controls -->
    <div class="category-pills-row">
        <div class="category-pills">
            <a
                href="<?= e(buildCatalogUrl('', $search, $sort)) ?>"
                class="category-pill <?= $categoryId === '' ? 'active' : '' ?>"
            >
                Semua
            </a>
            <?php foreach ($categories as $cat): ?>
                <a
                    href="<?= e(buildCatalogUrl((int)$cat['category_id'], $search, $sort)) ?>"
                    class="category-pill <?= (string)$categoryId === (string)$cat['category_id'] ? 'active' : '' ?>"
                >
                    <?= e($cat['category_name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <span style="font-size: 13px; color: var(--text-secondary);">
                Menampilkan <strong style="color: var(--text-primary);"><?= count($books) ?></strong> buku
            </span>

            <form method="GET" action="catalog.php" style="display: inline-flex; align-items: center; gap: 8px;">
                <?php if ($search !== ''): ?>
                    <input type="hidden" name="q" value="<?= e($search) ?>">
                <?php endif; ?>
                <?php if ($categoryId !== ''): ?>
                    <input type="hidden" name="category_id" value="<?= e((string)$categoryId) ?>">
                <?php endif; ?>
                <select
                    name="sort"
                    class="filter-select"
                    style="height: 36px; min-width: 165px; font-size: 12px; font-weight: 600; color: var(--text-primary);"
                    onchange="this.form.submit()"
                    aria-label="Urutkan buku"
                >
                    <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Urutkan: Terbaru</option>
                    <option value="judul" <?= $sort === 'judul' ? 'selected' : '' ?>>Urutkan: Judul (A-Z)</option>
                    <option value="tahun" <?= $sort === 'tahun' ? 'selected' : '' ?>>Urutkan: Tahun Terbit</option>
                </select>
            </form>

            <?php if ($search !== '' || $categoryId !== '' || $sort !== 'terbaru'): ?>
                <a href="catalog.php" class="btn btn-secondary btn-sm">Reset</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section Heading -->
    <div class="section-heading-row">
        <div>
            <h2 class="section-title">Pilihan untuk Anda</h2>
            <p class="section-subtitle">Koleksi buku yang tersedia di perpustakaan</p>
        </div>
    </div>

    <!-- Book Grid (Sesuai buku yang ada di database) -->
    <?php if (count($books) > 0): ?>
        <div class="book-grid">
            <?php foreach ($books as $book): ?>
                <a href="detail_book.php?id=<?= (int)$book['book_id'] ?>" class="book-card">
                    <div class="book-card-cover-wrap">
                        <?= renderBookCover($book) ?>
                    </div>
                    <span class="book-card-category"><?= e($book['category_name']) ?></span>
                    <h3 class="book-card-title"><?= e($book['title']) ?></h3>
                    <p class="book-card-author"><?= e($book['author']) ?></p>
                    <span class="book-card-meta">
                        PDF · <?= !empty($book['publication_year']) ? (int)$book['publication_year'] : 'DIGITAL' ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="panel-card">
            <div class="empty-state">
                <div class="empty-state-title">Buku tidak ditemukan</div>
                <p>Tidak ada koleksi buku yang cocok dengan pencarian atau filter kategori yang dipilih.</p>
                <a href="catalog.php" class="btn btn-secondary btn-sm" style="margin-top: 12px;">Lihat Semua Koleksi</a>
            </div>
        </div>
    <?php endif; ?>
</main>

<script src="assets/js/app.js"></script>
</body>
</html>
