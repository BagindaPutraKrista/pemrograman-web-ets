<?php
require_once __DIR__ . '/../includes/auth_check.php';

// Input parameters
$q             = trim($_GET['q'] ?? '');
$sort          = $_GET['sort'] ?? 'newest';
$category_id   = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
$category_name = trim($_GET['category_name'] ?? '');

// Resolve category_name → category_id if needed
if ($category_id === 0 && $category_name !== '') {
    $catStmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
    $catStmt->execute([$category_name]);
    $catRow = $catStmt->fetch(PDO::FETCH_ASSOC);
    if ($catRow) $category_id = (int) $catRow['id'];
}

// Build dynamic query
$params = [];
$where  = ["i.status = 'Tersedia'"];

if ($q !== '') {
    $where[]  = "(i.title LIKE ? OR i.description LIKE ?)";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}

if ($category_id > 0) {
    $where[]  = "i.category_id = ?";
    $params[] = $category_id;
}

$orderBy = match ($sort) {
    'price_high' => 'ORDER BY i.price DESC',
    'price_low'  => 'ORDER BY i.price ASC',
    'newest'     => 'ORDER BY i.created_at DESC',
    default      => 'ORDER BY i.created_at DESC', // relevance fallback
};

$sql = "
    SELECT i.*, ip.photo_path, u.name as seller_name
    FROM items i
    LEFT JOIN item_photos ip ON ip.item_id = i.id AND ip.is_primary = 1
    LEFT JOIN users u ON u.id = i.seller_id
    LEFT JOIN categories c ON c.id = i.category_id
    WHERE " . implode(' AND ', $where) . "
    $orderBy
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($items);

$pageTitle = 'Pencarian – Veteran Marketplace';

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-4">

    <!-- Search header -->
    <div class="mb-4">
        <?php if ($q !== ''): ?>
            <h5 class="fw-semibold mb-1">
                Hasil pencarian untuk: <span class="text-primary">"<?= htmlspecialchars($q) ?>"</span>
            </h5>
        <?php else: ?>
            <h5 class="fw-semibold mb-1">Semua Barang</h5>
        <?php endif; ?>
        <p class="text-muted small mb-0">
            <?= $total ?> barang ditemukan
            <?php if ($sort !== 'relevance' && $sort !== 'newest'): ?>
                &middot; Diurutkan: <?= match($sort) {
                    'price_high' => 'Harga Tertinggi',
                    'price_low'  => 'Harga Terendah',
                    default      => ''
                } ?>
            <?php endif; ?>
        </p>
    </div>

    <?php if (empty($items)): ?>
        <!-- Empty state -->
        <div class="text-center py-5 text-muted">
            <i class="bi bi-search fs-1 d-block mb-3"></i>
            <p class="mb-0 fs-5">Tidak ada barang yang ditemukan</p>
            <p class="small mt-1">Coba kata kunci lain atau hapus filter yang diterapkan</p>
            <a href="../pages/home.php" class="btn btn-primary mt-3">
                <i class="bi bi-house me-2"></i>Kembali ke Beranda
            </a>
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
