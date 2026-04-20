<?php
require_once __DIR__ . '/../includes/auth_check.php';

// Get and validate slug
$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    header('Location: home.php');
    exit;
}

// Query category by slug
$catStmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
$catStmt->execute([$slug]);
$category = $catStmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header('Location: home.php');
    exit;
}

// Query items in this category with primary photo
$stmt = $pdo->prepare("
    SELECT i.*, ip.photo_path, u.name as seller_name
    FROM items i
    LEFT JOIN item_photos ip ON ip.item_id = i.id AND ip.is_primary = 1
    LEFT JOIN users u ON u.id = i.seller_id
    WHERE i.category_id = ? AND i.status = 'Tersedia'
    ORDER BY i.created_at DESC
");
$stmt->execute([$category['id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = htmlspecialchars($category['name']) . ' – Veteran Marketplace';

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-4">

    <!-- Page header -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="home.php">Beranda</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($category['name']) ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-1"><?= htmlspecialchars($category['name']) ?></h4>
        <p class="text-muted small mb-0"><?= count($items) ?> barang tersedia</p>
    </div>

    <?php if (empty($items)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
            <p class="mb-0 fs-5">Belum ada barang dalam kategori ini</p>
            <a href="home.php" class="btn btn-primary mt-3">
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
