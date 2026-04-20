<?php
require_once __DIR__ . '/../includes/auth_check.php';

$userId        = (int) $currentUser['id'];
$transactionId = isset($_GET['transaction_id']) ? (int) $_GET['transaction_id'] : 0;

if ($transactionId <= 0) {
    header('Location: transaction_history.php');
    exit;
}

// Verify: transaction exists, buyer_id = current user, status = 'Selesai'
$txStmt = $pdo->prepare("
    SELECT t.id, t.buyer_id, t.seller_id, t.status,
           i.title AS item_title,
           u.name  AS seller_name
    FROM transactions t
    JOIN items i ON i.id = t.item_id
    JOIN users u ON u.id = t.seller_id
    WHERE t.id = ? AND t.buyer_id = ? AND t.status = 'Selesai'
    LIMIT 1
");
$txStmt->execute([$transactionId, $userId]);
$transaction = $txStmt->fetch();

if (!$transaction) {
    flashMessage('error', 'Transaksi tidak ditemukan atau tidak valid.');
    header('Location: transaction_history.php');
    exit;
}

// Check for existing review
$existingStmt = $pdo->prepare(
    "SELECT id FROM reviews WHERE transaction_id = ? LIMIT 1"
);
$existingStmt->execute([$transactionId]);
$existingReview = $existingStmt->fetch();

$error = null;

// ── POST Handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating  = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
    $comment = trim($_POST['comment'] ?? '');

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $error = 'Pilih rating terlebih dahulu';
    } else {
        // Check duplicate (race condition guard)
        $dupStmt = $pdo->prepare(
            "SELECT id FROM reviews WHERE transaction_id = ? LIMIT 1"
        );
        $dupStmt->execute([$transactionId]);
        if ($dupStmt->fetch()) {
            $error = 'Anda sudah memberikan ulasan untuk transaksi ini';
        } else {
            try {
                $insertStmt = $pdo->prepare("
                    INSERT INTO reviews (transaction_id, buyer_id, seller_id, rating, comment)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $insertStmt->execute([
                    $transactionId,
                    $userId,
                    (int) $transaction['seller_id'],
                    $rating,
                    $comment,
                ]);

                flashMessage('success', 'Ulasan berhasil dikirim. Terima kasih!');
                header('Location: transaction_history.php');
                exit;
            } catch (PDOException $e) {
                // UNIQUE constraint violation
                if ($e->getCode() === '23000') {
                    $error = 'Anda sudah memberikan ulasan untuk transaksi ini';
                } else {
                    $error = 'Gagal menyimpan ulasan. Coba lagi.';
                    error_log('Review insert error: ' . $e->getMessage());
                }
            }
        }
    }
}

$pageTitle = 'Beri Ulasan – Veteran Marketplace';
require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-4" style="max-width: 600px;">

    <div class="mb-3">
        <a href="transaction_history.php" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Riwayat Transaksi
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">

            <h5 class="fw-semibold mb-1">
                <i class="bi bi-star me-2 text-warning"></i>Beri Ulasan
            </h5>
            <p class="text-muted small mb-4">
                Transaksi: <strong><?= htmlspecialchars($transaction['item_title']) ?></strong>
                &nbsp;·&nbsp; Penjual: <strong><?= htmlspecialchars($transaction['seller_name']) ?></strong>
            </p>

            <?php if ($existingReview): ?>
                <!-- Already reviewed -->
                <div class="alert alert-info d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>Anda sudah memberikan ulasan untuk transaksi ini.</span>
                </div>
                <a href="transaction_history.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>

            <?php else: ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" novalidate>

                    <!-- Star Rating -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Rating <span class="text-danger">*</span>
                        </label>
                        <div class="star-rating" role="group" aria-label="Rating bintang">
                            <?php
                            $selectedRating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
                            for ($i = 5; $i >= 1; $i--):
                            ?>
                                <input
                                    type="radio"
                                    name="rating"
                                    id="star<?= $i ?>"
                                    value="<?= $i ?>"
                                    <?= $selectedRating === $i ? 'checked' : '' ?>
                                >
                                <label for="star<?= $i ?>" title="<?= $i ?> bintang">
                                    <i class="bi bi-star-fill"></i>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <div class="form-text mt-1">Pilih 1 hingga 5 bintang</div>
                    </div>

                    <!-- Comment -->
                    <div class="mb-4">
                        <label for="comment" class="form-label fw-semibold">Komentar</label>
                        <textarea
                            id="comment"
                            name="comment"
                            class="form-control"
                            rows="4"
                            placeholder="Ceritakan pengalaman transaksi Anda..."
                            maxlength="1000"
                        ><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
                        <div class="form-text">Opsional, maksimal 1000 karakter</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-warning fw-semibold px-4">
                            <i class="bi bi-send me-1"></i>Kirim Ulasan
                        </button>
                        <a href="transaction_history.php" class="btn btn-outline-secondary">
                            Batal
                        </a>
                    </div>

                </form>

            <?php endif; ?>

        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
