<?php
require_once __DIR__ . '/../includes/auth_check.php';

// Query all wishlist items for current user with primary photo
$stmt = $pdo->prepare("
    SELECT i.*, ip.photo_path, u.name as seller_name, w.id as wishlist_id
    FROM wishlists w
    JOIN items i ON i.id = w.item_id
    LEFT JOIN item_photos ip ON ip.item_id = i.id AND ip.is_primary = 1
    LEFT JOIN users u ON u.id = i.seller_id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$currentUser['id']]);
$wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Wishlist – Veteran Marketplace';

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-4">
    <h5 class="fw-semibold mb-4">
        <i class="bi bi-heart-fill text-danger me-2"></i>Wishlist Saya
        <span class="badge bg-secondary ms-2 fs-6"><?= count($wishlistItems) ?></span>
    </h5>

    <?php if (empty($wishlistItems)): ?>
        <!-- Empty state -->
        <div class="text-center py-5 text-muted">
            <i class="bi bi-heart fs-1 d-block mb-3"></i>
            <p class="mb-0 fs-5">Belum ada barang yang disimpan</p>
            <p class="small mt-1">Temukan barang menarik dan simpan ke wishlist kamu</p>
            <a href="../pages/home.php" class="btn btn-primary mt-3">
                <i class="bi bi-house me-2"></i>Jelajahi Barang
            </a>
        </div>
    <?php else: ?>
        <div class="row g-3" id="wishlist-grid">
            <?php foreach ($wishlistItems as $item): ?>
                <div class="col-6 col-md-4 col-lg-3" id="wishlist-card-<?= (int) $item['wishlist_id'] ?>">
                    <div class="position-relative">
                        <!-- Remove button -->
                        <button
                            type="button"
                            class="btn btn-danger btn-sm position-absolute top-0 start-0 m-2 z-1 rounded-circle btn-remove-wishlist"
                            style="width:32px; height:32px; padding:0; z-index:10;"
                            data-item-id="<?= (int) $item['id'] ?>"
                            data-wishlist-card="wishlist-card-<?= (int) $item['wishlist_id'] ?>"
                            title="Hapus dari wishlist"
                        >
                            <i class="bi bi-x"></i>
                        </button>
                        <?php require __DIR__ . '/../includes/item_card.php'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script>
document.querySelectorAll('.btn-remove-wishlist').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var itemId   = this.dataset.itemId;
        var cardId   = this.dataset.wishlistCard;
        var btnEl    = this;

        btnEl.disabled = true;

        fetch('../api/wishlist_toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'item_id=' + encodeURIComponent(itemId)
        })
        .then(function(r) {
            if (!r.ok) throw new Error('Server error');
            return r.json();
        })
        .then(function(data) {
            if (!data.in_wishlist) {
                var card = document.getElementById(cardId);
                if (card) {
                    card.style.transition = 'opacity 0.3s';
                    card.style.opacity = '0';
                    setTimeout(function() {
                        card.remove();
                        // Show empty state if no cards left
                        var grid = document.getElementById('wishlist-grid');
                        if (grid && grid.children.length === 0) {
                            grid.outerHTML = '<div class="text-center py-5 text-muted">'
                                + '<i class="bi bi-heart fs-1 d-block mb-3"></i>'
                                + '<p class="mb-0 fs-5">Belum ada barang yang disimpan</p>'
                                + '<p class="small mt-1">Temukan barang menarik dan simpan ke wishlist kamu</p>'
                                + '<a href="../pages/home.php" class="btn btn-primary mt-3">'
                                + '<i class="bi bi-house me-2"></i>Jelajahi Barang</a></div>';
                        }
                    }, 300);
                }
            }
        })
        .catch(function() {
            btnEl.disabled = false;
            alert('Gagal menghapus dari wishlist. Coba lagi.');
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
