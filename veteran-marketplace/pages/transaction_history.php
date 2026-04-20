<?php
require_once __DIR__ . '/../includes/auth_check.php';

$userId = (int) $currentUser['id'];
$error  = null;
$success = null;

// ── POST Handler (PRG pattern) ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $txId   = (int) ($_POST['transaction_id'] ?? 0);

    if ($action === 'confirm_received' && $txId > 0) {
        // Buyer confirms receipt: status → Selesai, item → Terjual
        $stmt = $pdo->prepare("
            SELECT t.*, i.title AS item_title
            FROM transactions t
            JOIN items i ON i.id = t.item_id
            WHERE t.id = ? AND t.buyer_id = ? AND t.status = 'Diproses'
            LIMIT 1
        ");
        $stmt->execute([$txId, $userId]);
        $tx = $stmt->fetch();

        if ($tx) {
            $pdo->beginTransaction();
            try {
                $pdo->prepare("UPDATE transactions SET status = 'Selesai' WHERE id = ?")
                    ->execute([$txId]);
                $pdo->prepare("UPDATE items SET status = 'Terjual' WHERE id = ?")
                    ->execute([$tx['item_id']]);

                $txLink = '../pages/transaction_history.php';
                // Notify buyer
                createNotification(
                    $pdo,
                    $tx['buyer_id'],
                    'transaction',
                    'Transaksi untuk "' . $tx['item_title'] . '" telah selesai. Silakan beri ulasan.',
                    $txLink
                );
                // Notify seller
                createNotification(
                    $pdo,
                    $tx['seller_id'],
                    'transaction',
                    'Pembeli telah mengkonfirmasi penerimaan "' . $tx['item_title'] . '". Transaksi selesai.',
                    $txLink
                );

                $pdo->commit();
                flashMessage('success', 'Penerimaan barang dikonfirmasi. Transaksi selesai!');
            } catch (Exception $e) {
                $pdo->rollBack();
                flashMessage('error', 'Gagal mengkonfirmasi. Coba lagi.');
            }
        } else {
            flashMessage('error', 'Transaksi tidak ditemukan atau tidak valid.');
        }

    } elseif ($action === 'process_order' && $txId > 0) {
        // Seller processes order: status → Diproses, item → Dipesan
        $stmt = $pdo->prepare("
            SELECT t.*, i.title AS item_title
            FROM transactions t
            JOIN items i ON i.id = t.item_id
            WHERE t.id = ? AND t.seller_id = ? AND t.status = 'Menunggu'
            LIMIT 1
        ");
        $stmt->execute([$txId, $userId]);
        $tx = $stmt->fetch();

        if ($tx) {
            $pdo->beginTransaction();
            try {
                $pdo->prepare("UPDATE transactions SET status = 'Diproses' WHERE id = ?")
                    ->execute([$txId]);
                $pdo->prepare("UPDATE items SET status = 'Dipesan' WHERE id = ?")
                    ->execute([$tx['item_id']]);

                $txLink = '../pages/transaction_history.php';
                // Notify seller
                createNotification(
                    $pdo,
                    $tx['seller_id'],
                    'transaction',
                    'Pesanan untuk "' . $tx['item_title'] . '" sedang diproses.',
                    $txLink
                );
                // Notify buyer
                createNotification(
                    $pdo,
                    $tx['buyer_id'],
                    'transaction',
                    'Penjual sedang memproses pesanan Anda untuk "' . $tx['item_title'] . '".',
                    $txLink
                );

                $pdo->commit();
                flashMessage('success', 'Pesanan berhasil diproses!');
            } catch (Exception $e) {
                $pdo->rollBack();
                flashMessage('error', 'Gagal memproses pesanan. Coba lagi.');
            }
        } else {
            flashMessage('error', 'Transaksi tidak ditemukan atau tidak valid.');
        }
    }

    header('Location: transaction_history.php');
    exit;
}

// ── Fetch transactions as Buyer ───────────────────────────────────────────────
$stmtBuyer = $pdo->prepare("
    SELECT
        t.id, t.status, t.created_at, t.updated_at,
        i.id AS item_id, i.title AS item_title, i.price, i.status AS item_status,
        ip.photo_path,
        u.id AS seller_id, u.name AS seller_name, u.profile_photo AS seller_photo,
        r.id AS review_id
    FROM transactions t
    JOIN items i ON i.id = t.item_id
    LEFT JOIN item_photos ip ON ip.item_id = i.id AND ip.is_primary = 1
    JOIN users u ON u.id = t.seller_id
    LEFT JOIN reviews r ON r.transaction_id = t.id AND r.buyer_id = ?
    WHERE t.buyer_id = ?
    ORDER BY t.updated_at DESC
");
$stmtBuyer->execute([$userId, $userId]);
$buyerTransactions = $stmtBuyer->fetchAll();

// ── Fetch transactions as Seller ──────────────────────────────────────────────
$stmtSeller = $pdo->prepare("
    SELECT
        t.id, t.status, t.created_at, t.updated_at,
        i.id AS item_id, i.title AS item_title, i.price, i.status AS item_status,
        ip.photo_path,
        u.id AS buyer_id, u.name AS buyer_name, u.profile_photo AS buyer_photo
    FROM transactions t
    JOIN items i ON i.id = t.item_id
    LEFT JOIN item_photos ip ON ip.item_id = i.id AND ip.is_primary = 1
    JOIN users u ON u.id = t.buyer_id
    WHERE t.seller_id = ?
    ORDER BY t.updated_at DESC
");
$stmtSeller->execute([$userId]);
$sellerTransactions = $stmtSeller->fetchAll();

$pageTitle = 'Riwayat Transaksi – Veteran Marketplace';
require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-4" style="max-width: 860px;">

    <h5 class="fw-semibold mb-4">
        <i class="bi bi-arrow-left-right me-2"></i>Riwayat Transaksi
    </h5>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="txTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button
                class="nav-link active"
                id="buyer-tab"
                data-bs-toggle="tab"
                data-bs-target="#buyer-pane"
                type="button"
                role="tab"
            >
                <i class="bi bi-bag me-1"></i>Sebagai Pembeli
                <?php if (count($buyerTransactions) > 0): ?>
                    <span class="badge bg-secondary ms-1"><?= count($buyerTransactions) ?></span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button
                class="nav-link"
                id="seller-tab"
                data-bs-toggle="tab"
                data-bs-target="#seller-pane"
                type="button"
                role="tab"
            >
                <i class="bi bi-shop me-1"></i>Sebagai Penjual
                <?php if (count($sellerTransactions) > 0): ?>
                    <span class="badge bg-secondary ms-1"><?= count($sellerTransactions) ?></span>
                <?php endif; ?>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="txTabContent">

        <!-- ── Buyer Tab ──────────────────────────────────────────────────── -->
        <div class="tab-pane fade show active" id="buyer-pane" role="tabpanel">
            <?php if (empty($buyerTransactions)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bag-x fs-1 d-block mb-3"></i>
                    <p class="mb-0 fs-5">Belum ada transaksi sebagai pembeli</p>
                    <a href="home.php" class="btn btn-primary mt-3">Mulai Belanja</a>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($buyerTransactions as $tx): ?>
                        <?php
                        $statusBadge = match ($tx['status']) {
                            'Menunggu' => 'bg-warning text-dark',
                            'Diproses' => 'bg-info text-dark',
                            'Selesai'  => 'bg-success',
                            default    => 'bg-secondary',
                        };
                        $photoSrc = !empty($tx['photo_path'])
                            ? htmlspecialchars($tx['photo_path'])
                            : 'https://placehold.co/80x80?text=No+Photo';
                        $sellerPhotoSrc = !empty($tx['seller_photo'])
                            ? htmlspecialchars($tx['seller_photo'])
                            : null;
                        ?>
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex gap-3 align-items-start">
                                    <!-- Item photo -->
                                    <a href="item_detail.php?id=<?= (int) $tx['item_id'] ?>">
                                        <img
                                            src="<?= $photoSrc ?>"
                                            alt="<?= htmlspecialchars($tx['item_title']) ?>"
                                            class="rounded"
                                            style="width:80px; height:80px; object-fit:cover; flex-shrink:0;"
                                        >
                                    </a>

                                    <!-- Details -->
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                            <div>
                                                <a
                                                    href="item_detail.php?id=<?= (int) $tx['item_id'] ?>"
                                                    class="fw-semibold text-decoration-none text-dark"
                                                >
                                                    <?= htmlspecialchars($tx['item_title']) ?>
                                                </a>
                                                <div class="text-primary fw-bold mt-1">
                                                    <?= formatPrice($tx['price']) ?>
                                                </div>
                                            </div>
                                            <span class="badge <?= $statusBadge ?> px-3 py-2">
                                                <?= htmlspecialchars($tx['status']) ?>
                                            </span>
                                        </div>

                                        <!-- Seller info -->
                                        <div class="d-flex align-items-center gap-2 mt-2 text-muted small">
                                            <?php if ($sellerPhotoSrc): ?>
                                                <img
                                                    src="<?= $sellerPhotoSrc ?>"
                                                    alt=""
                                                    class="rounded-circle"
                                                    style="width:20px; height:20px; object-fit:cover;"
                                                >
                                            <?php else: ?>
                                                <i class="bi bi-person-circle"></i>
                                            <?php endif; ?>
                                            <span>Penjual: <a href="profile.php?id=<?= (int) $tx['seller_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($tx['seller_name']) ?></a></span>
                                            <span class="ms-auto text-muted small" style="white-space:nowrap;">
                                                <?= date('d M Y, H:i', strtotime($tx['updated_at'])) ?>
                                            </span>
                                        </div>

                                        <!-- Action buttons -->
                                        <div class="d-flex gap-2 mt-3 flex-wrap">
                                            <?php if ($tx['status'] === 'Diproses'): ?>
                                                <form method="POST" onsubmit="return confirm('Konfirmasi bahwa Anda telah menerima barang ini?')">
                                                    <input type="hidden" name="action" value="confirm_received">
                                                    <input type="hidden" name="transaction_id" value="<?= (int) $tx['id'] ?>">
                                                    <button type="submit" class="btn btn-success btn-sm">
                                                        <i class="bi bi-check-circle me-1"></i>Konfirmasi Terima Barang
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($tx['status'] === 'Selesai' && empty($tx['review_id'])): ?>
                                                <a
                                                    href="review.php?transaction_id=<?= (int) $tx['id'] ?>"
                                                    class="btn btn-outline-warning btn-sm"
                                                >
                                                    <i class="bi bi-star me-1"></i>Beri Ulasan
                                                </a>
                                            <?php elseif ($tx['status'] === 'Selesai' && !empty($tx['review_id'])): ?>
                                                <span class="badge bg-light text-success border border-success px-3 py-2">
                                                    <i class="bi bi-star-fill me-1"></i>Sudah Diulas
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── Seller Tab ─────────────────────────────────────────────────── -->
        <div class="tab-pane fade" id="seller-pane" role="tabpanel">
            <?php if (empty($sellerTransactions)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-shop fs-1 d-block mb-3"></i>
                    <p class="mb-0 fs-5">Belum ada transaksi sebagai penjual</p>
                    <a href="sell_item.php" class="btn btn-success mt-3">
                        <i class="bi bi-plus-lg me-1"></i>Jual Barang
                    </a>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($sellerTransactions as $tx): ?>
                        <?php
                        $statusBadge = match ($tx['status']) {
                            'Menunggu' => 'bg-warning text-dark',
                            'Diproses' => 'bg-info text-dark',
                            'Selesai'  => 'bg-success',
                            default    => 'bg-secondary',
                        };
                        $photoSrc = !empty($tx['photo_path'])
                            ? htmlspecialchars($tx['photo_path'])
                            : 'https://placehold.co/80x80?text=No+Photo';
                        $buyerPhotoSrc = !empty($tx['buyer_photo'])
                            ? htmlspecialchars($tx['buyer_photo'])
                            : null;
                        ?>
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div class="d-flex gap-3 align-items-start">
                                    <!-- Item photo -->
                                    <a href="item_detail.php?id=<?= (int) $tx['item_id'] ?>">
                                        <img
                                            src="<?= $photoSrc ?>"
                                            alt="<?= htmlspecialchars($tx['item_title']) ?>"
                                            class="rounded"
                                            style="width:80px; height:80px; object-fit:cover; flex-shrink:0;"
                                        >
                                    </a>

                                    <!-- Details -->
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                            <div>
                                                <a
                                                    href="item_detail.php?id=<?= (int) $tx['item_id'] ?>"
                                                    class="fw-semibold text-decoration-none text-dark"
                                                >
                                                    <?= htmlspecialchars($tx['item_title']) ?>
                                                </a>
                                                <div class="text-primary fw-bold mt-1">
                                                    <?= formatPrice($tx['price']) ?>
                                                </div>
                                            </div>
                                            <span class="badge <?= $statusBadge ?> px-3 py-2">
                                                <?= htmlspecialchars($tx['status']) ?>
                                            </span>
                                        </div>

                                        <!-- Buyer info -->
                                        <div class="d-flex align-items-center gap-2 mt-2 text-muted small">
                                            <?php if ($buyerPhotoSrc): ?>
                                                <img
                                                    src="<?= $buyerPhotoSrc ?>"
                                                    alt=""
                                                    class="rounded-circle"
                                                    style="width:20px; height:20px; object-fit:cover;"
                                                >
                                            <?php else: ?>
                                                <i class="bi bi-person-circle"></i>
                                            <?php endif; ?>
                                            <span>Pembeli: <a href="profile.php?id=<?= (int) $tx['buyer_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($tx['buyer_name']) ?></a></span>
                                            <span class="ms-auto text-muted small" style="white-space:nowrap;">
                                                <?= date('d M Y, H:i', strtotime($tx['updated_at'])) ?>
                                            </span>
                                        </div>

                                        <!-- Action buttons -->
                                        <div class="d-flex gap-2 mt-3 flex-wrap">
                                            <?php if ($tx['status'] === 'Menunggu'): ?>
                                                <form method="POST" onsubmit="return confirm('Proses pesanan ini?')">
                                                    <input type="hidden" name="action" value="process_order">
                                                    <input type="hidden" name="transaction_id" value="<?= (int) $tx['id'] ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        <i class="bi bi-box-seam me-1"></i>Proses Pesanan
                                                    </button>
                                                </form>
                                            <?php elseif ($tx['status'] === 'Diproses'): ?>
                                                <span class="badge bg-info text-dark px-3 py-2">
                                                    <i class="bi bi-hourglass-split me-1"></i>Menunggu Konfirmasi Pembeli
                                                </span>
                                            <?php elseif ($tx['status'] === 'Selesai'): ?>
                                                <span class="badge bg-success px-3 py-2">
                                                    <i class="bi bi-check-circle me-1"></i>Transaksi Selesai
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- /tab-content -->
</main>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
