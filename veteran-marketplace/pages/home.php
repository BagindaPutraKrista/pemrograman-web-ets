<?php
require_once __DIR__ . '/../includes/auth_check.php';

// Query items with status='Tersedia', ordered by created_at DESC, with primary photo JOIN
$stmt = $pdo->prepare("
    SELECT i.*, ip.photo_path, u.name as seller_name, c.name as category_name
    FROM items i
    LEFT JOIN item_photos ip ON ip.item_id = i.id AND ip.is_primary = 1
    LEFT JOIN users u ON u.id = i.seller_id
    LEFT JOIN categories c ON c.id = i.category_id
    WHERE i.status = 'Tersedia'
    ORDER BY i.created_at DESC
    LIMIT 24
");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query categories for quick-links
$catStmt = $pdo->query("SELECT id, name, slug FROM categories ORDER BY id LIMIT 8");
$homeCategories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Beranda – Veteran Marketplace';

// Category icons mapping
$categoryIcons = [
    'elektronik'         => 'bi-cpu',
    'buku-alat-tulis'    => 'bi-book',
    'pakaian-fashion'    => 'bi-bag',
    'perabot-furnitur'   => 'bi-house',
    'olahraga-hobi'      => 'bi-bicycle',
    'kendaraan-aksesori' => 'bi-car-front',
    'makanan-minuman'    => 'bi-cup-straw',
    'lainnya'            => 'bi-grid',
];

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- ── Hero Section ─────────────────────────────────────────── -->
<section class="py-5" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);">
    <div class="container text-center text-white py-3">
        <h1 class="fw-bold mb-3">Jual Beli Barang Bekas Sesama Mahasiswa UPN Veteran Jatim</h1>
        <p class="lead mb-4 opacity-75">Temukan barang berkualitas dengan harga terjangkau dari sesama mahasiswa</p>
        <a href="../pages/sell_item.php" class="btn btn-light btn-lg fw-semibold px-4">
            <i class="bi bi-plus-lg me-2"></i>Jual Sekarang
        </a>
    </div>
</section>

<!-- ── Category Quick-Links ─────────────────────────────────── -->
<section class="py-4 bg-white border-bottom">
    <div class="container">
        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <?php foreach ($homeCategories as $cat):
                $icon = $categoryIcons[$cat['slug']] ?? 'bi-grid';
            ?>
            <a
                href="../pages/category.php?slug=<?= htmlspecialchars($cat['slug']) ?>"
                class="btn btn-outline-secondary d-flex align-items-center gap-2 rounded-pill px-3 py-2"
            >
                <i class="bi <?= $icon ?>"></i>
                <span class="small"><?= htmlspecialchars($cat['name']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── Items Grid ────────────────────────────────────────────── -->
<main class="container py-4">
    <h5 class="fw-semibold mb-3">Barang Terbaru</h5>

    <?php if (empty($items)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
            <p class="mb-0">Belum ada barang yang tersedia saat ini.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($items as $item): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <?php require __DIR__ . '/../includes/item_card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
