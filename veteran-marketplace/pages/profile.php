<?php
require_once __DIR__ . '/../includes/auth_check.php';

// Get profile user id, default to current user
$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) $currentUser['id'];
if ($id <= 0) {
    header('Location: home.php');
    exit;
}

// Query user
$userStmt = $pdo->prepare(
    "SELECT id, name, bio, contact, profile_photo, created_at FROM users WHERE id = ? LIMIT 1"
);
$userStmt->execute([$id]);
$profileUser = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$profileUser) {
    header('Location: home.php');
    exit;
}

// Avg rating + review count
$ratingStmt = $pdo->prepare(
    "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE seller_id = ?"
);
$ratingStmt->execute([$id]);
$ratingData = $ratingStmt->fetch(PDO::FETCH_ASSOC);
$avgRating    = $ratingData['avg_rating'] ? round((float) $ratingData['avg_rating'], 1) : 0;
$totalReviews = (int) $ratingData['total_reviews'];

// Items for sale (Tersedia)
$itemsForSaleStmt = $pdo->prepare("
    SELECT i.*, ip.photo_path
    FROM items i
    LEFT JOIN item_photos ip ON ip.item_id = i.id AND ip.is_primary = 1
    WHERE i.seller_id = ? AND i.status = 'Tersedia'
    ORDER BY i.created_at DESC
");
$itemsForSaleStmt->execute([$id]);
$itemsForSale = $itemsForSaleStmt->fetchAll(PDO::FETCH_ASSOC);

// Items sold (Terjual)
$itemsSoldStmt = $pdo->prepare("
    SELECT i.*, ip.photo_path
    FROM items i
    LEFT JOIN item_photos ip ON ip.item_id = i.id AND ip.is_primary = 1
    WHERE i.seller_id = ? AND i.status = 'Terjual'
    ORDER BY i.created_at DESC
");
$itemsSoldStmt->execute([$id]);
$itemsSold = $itemsSoldStmt->fetchAll(PDO::FETCH_ASSOC);

// Reviews received
$reviewsStmt = $pdo->prepare("
    SELECT r.*, u.name as buyer_name, u.profile_photo as buyer_photo
    FROM reviews r
    JOIN users u ON u.id = r.buyer_id
    WHERE r.seller_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$reviewsStmt->execute([$id]);
$reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

$isOwnProfile = ((int) $currentUser['id'] === $id);
$pageTitle = htmlspecialchars($profileUser['name']) . ' – Profil';

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-4">

    <!-- Profile header card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start gap-4">

                <!-- Profile photo -->
                <?php if (!empty($profileUser['profile_photo'])): ?>
                    <img
                        src="<?= htmlspecialchars($profileUser['profile_photo']) ?>"
                        alt="<?= htmlspecialchars($profileUser['name']) ?>"
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
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($profileUser['name']) ?></h4>

                    <?php if (!empty($profileUser['bio'])): ?>
                        <p class="text-muted mb-2"><?= htmlspecialchars($profileUser['bio']) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($profileUser['contact'])): ?>
                        <p class="small text-muted mb-2">
                            <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($profileUser['contact']) ?>
                        </p>
                    <?php endif; ?>

                    <p class="small text-muted mb-2">
                        <i class="bi bi-calendar3 me-1"></i>
                        Bergabung <?= date('F Y', strtotime($profileUser['created_at'])) ?>
                    </p>

                    <!-- Rating -->
                    <div class="d-flex align-items-center gap-2 justify-content-center justify-content-md-start mb-3">
                        <span class="stars-display">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="bi <?= $s <= round($avgRating) ? 'bi-star-fill' : 'bi-star' ?> <?= $s <= round($avgRating) ? '' : 'star-empty' ?>"></i>
                            <?php endfor; ?>
                        </span>
                        <span class="fw-semibold"><?= $avgRating > 0 ? $avgRating : '-' ?></span>
                        <span class="text-muted small">(<?= $totalReviews ?> ulasan)</span>
                    </div>

                    <!-- Action buttons -->
                    <div class="d-flex gap-2 justify-content-center justify-content-md-start">
                        <?php if ($isOwnProfile): ?>
                            <a href="edit_profile.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-pencil me-1"></i>Edit Profil
                            </a>
                        <?php else: ?>
                            <a href="seller_detail.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-shop me-1"></i>Lihat Toko
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-dijual" data-bs-toggle="tab" data-bs-target="#pane-dijual" type="button" role="tab">
                Dijual
                <span class="badge bg-secondary ms-1"><?= count($itemsForSale) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-terjual" data-bs-toggle="tab" data-bs-target="#pane-terjual" type="button" role="tab">
                Terjual
                <span class="badge bg-secondary ms-1"><?= count($itemsSold) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-ulasan" data-bs-toggle="tab" data-bs-target="#pane-ulasan" type="button" role="tab">
                Ulasan
                <span class="badge bg-secondary ms-1"><?= $totalReviews ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="profileTabsContent">

        <!-- Tab: Dijual -->
        <div class="tab-pane fade show active" id="pane-dijual" role="tabpanel">
            <?php if (empty($itemsForSale)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bag fs-1 d-block mb-3"></i>
                    <p class="mb-0">Belum ada barang yang dijual</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($itemsForSale as $item): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <?php require __DIR__ . '/../includes/item_card.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab: Terjual -->
        <div class="tab-pane fade" id="pane-terjual" role="tabpanel">
            <?php if (empty($itemsSold)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bag-check fs-1 d-block mb-3"></i>
                    <p class="mb-0">Belum ada barang yang terjual</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($itemsSold as $item): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <?php require __DIR__ . '/../includes/item_card.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tab: Ulasan -->
        <div class="tab-pane fade" id="pane-ulasan" role="tabpanel">
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
                                            class="rounded-circle object-fit-cover"
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
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
