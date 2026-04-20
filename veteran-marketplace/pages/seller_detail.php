<?php
require_once __DIR__ . '/../includes/auth_check.php';

// Get and validate seller id
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: home.php');
    exit;
}

// Query seller
$sellerStmt = $pdo->prepare(
    "SELECT id, name, bio, contact, profile_photo, created_at FROM users WHERE id = ? LIMIT 1"
);
$sellerStmt->execute([$id]);
$seller = $sellerStmt->fetch(PDO::FETCH_ASSOC);

if (!$seller) {
    header('Location: home.php');
    exit;
}

// Avg rating + review count
$ratingStmt = $pdo->prepare(
    "SELECT AVG(r.rating) as avg_rating, COUNT(r.id) as total_reviews FROM reviews r WHERE r.seller_id = ?"
);
$ratingStmt->execute([$id]);
$ratingData   = $ratingStmt->fetch(PDO::FETCH_ASSOC);
$avgRating    = $ratingData['avg_rating'] ? round((float) $ratingData['avg_rating'], 1) : 0;
$totalReviews = (int) $ratingData['total_reviews'];

// Total completed transactions
$txStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM transactions WHERE seller_id = ? AND status = 'Selesai'"
);
$txStmt->execute([$id]);
$totalCompleted = (int) $txStmt->fetchColumn();

// Items for sale (Tersedia or Dipesan)
$itemsStmt = $pdo->prepare("
    SELECT i.*, ip.photo_path, u.name as seller_name
    FROM items i
    LEFT JOIN item_photos ip ON ip.item_id = i.id AND ip.is_primary = 1
    LEFT JOIN users u ON u.id = i.seller_id
    WHERE i.seller_id = ? AND i.status IN ('Tersedia', 'Dipesan')
    ORDER BY i.created_at DESC
");
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Reviews
$reviewsStmt = $pdo->prepare("
    SELECT r.*, u.name as buyer_name, u.profile_photo as buyer_photo
    FROM reviews r
    JOIN users u ON u.id = r.buyer_id
    WHERE r.seller_id = ?
    ORDER BY r.created_at DESC
");
$reviewsStmt->execute([$id]);
$reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = htmlspecialchars($seller['name']) . ' – Toko';

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-4">

    <!-- Seller header card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start gap-4">

                <!-- Profile photo -->
                <?php if (!empty($seller['profile_photo'])): ?>
                    <img
                        src="<?= htmlspecialchars($seller['profile_photo']) ?>"
                        alt="<?= htmlspecialchars($seller['name']) ?>"
                        class="rounded-circle object-fit-cover flex-shrink-0"
                        style="width:100px; height:100px;"
                    >
                <?php else: ?>
                    <div
                        class="rounded-circle bg-secondary d-flex align-items-center justify-content-center flex-shrink-0 text-white"
                        style="width:100px; height:100px; font-size:2.5rem;"
                    >
                        <i class="bi bi-person-fill"></i>
                    </div>
                <?php endif; ?>

                <!-- Info -->
                <div class="flex-grow-1 text-center text-md-start">
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($seller['name']) ?></h4>

                    <?php if (!empty($seller['bio'])): ?>
                        <p class="text-muted mb-2"><?= htmlspecialchars($seller['bio']) ?></p>
                    <?php endif; ?>

                    <!-- Rating -->
                    <div class="d-flex align-items-center gap-2 justify-content-center justify-content-md-start mb-2">
                        <span class="stars-display">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="bi <?= $s <= round($avgRating) ? 'bi-star-fill' : 'bi-star star-empty' ?>"></i>
                            <?php endfor; ?>
                        </span>
                        <span class="fw-semibold"><?= $avgRating > 0 ? $avgRating : '-' ?></span>
                        <span class="text-muted small">(<?= $totalReviews ?> ulasan)</span>
                    </div>

                    <!-- Stats -->
                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-md-start mb-2">
                        <span class="text-muted small">
                            <i class="bi bi-bag-check me-1"></i>
                            <?= $totalCompleted ?> transaksi selesai
                        </span>
                        <span class="text-muted small">
                            <i class="bi bi-calendar3 me-1"></i>
                            Bergabung <?= date('F Y', strtotime($seller['created_at'])) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Items section -->
    <section class="mb-5">
        <h5 class="fw-semibold mb-3">
            <i class="bi bi-bag me-2"></i>Barang Dijual
        </h5>

        <?php if (empty($items)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-bag fs-1 d-block mb-3"></i>
                <p class="mb-0">Belum ada barang yang dijual</p>
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
    </section>

    <!-- Reviews section -->
    <section>
        <h5 class="fw-semibold mb-3">
            <i class="bi bi-chat-square-text me-2"></i>Ulasan Pembeli
        </h5>

        <?php if (empty($reviews)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-chat-square-text fs-1 d-block mb-3"></i>
                <p class="mb-0">Belum ada ulasan</p>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($reviews as $review): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <?php if (!empty($review['buyer_photo'])): ?>
                                    <img
                                        src="<?= htmlspecialchars($review['buyer_photo']) ?>"
                                        alt=""
                                        class="rounded-circle object-fit-cover flex-shrink-0"
                                        style="width:40px; height:40px;"
                                    >
                                <?php else: ?>
                                    <div
                                        class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white flex-shrink-0"
                                        style="width:40px; height:40px;"
                                    >
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-semibold small"><?= htmlspecialchars($review['buyer_name']) ?></div>
                                    <div class="stars-display" style="font-size:0.85rem;">
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <i class="bi <?= $s <= (int) $review['rating'] ? 'bi-star-fill' : 'bi-star star-empty' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <span class="ms-auto text-muted small"><?= date('d M Y', strtotime($review['created_at'])) ?></span>
                            </div>
                            <?php if (!empty($review['comment'])): ?>
                                <p class="mb-0 small"><?= htmlspecialchars($review['comment']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</main>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
