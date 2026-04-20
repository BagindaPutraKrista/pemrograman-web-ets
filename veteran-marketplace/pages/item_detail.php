<?php
require_once __DIR__ . '/../includes/auth_check.php';

// Validate item ID
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: home.php');
    exit;
}

// Query item with seller and category info
$stmt = $pdo->prepare("
    SELECT i.*, u.name as seller_name, u.profile_photo as seller_photo, u.id as seller_id,
           c.name as category_name, c.slug as category_slug
    FROM items i
    JOIN users u ON u.id = i.seller_id
    JOIN categories c ON c.id = i.category_id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header('Location: home.php');
    exit;
}

// Query all photos for this item
$photoStmt = $pdo->prepare("SELECT * FROM item_photos WHERE item_id = ? ORDER BY is_primary DESC");
$photoStmt->execute([$id]);
$photos = $photoStmt->fetchAll(PDO::FETCH_ASSOC);

// Query seller average rating
$ratingStmt = $pdo->prepare("SELECT AVG(rating) FROM reviews WHERE seller_id = ?");
$ratingStmt->execute([$item['seller_id']]);
$avgRating = (float) $ratingStmt->fetchColumn();

// Check if item is in current user's wishlist
$wishlistStmt = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ? AND item_id = ?");
$wishlistStmt->execute([(int) $currentUser['id'], $id]);
$inWishlist = (bool) $wishlistStmt->fetchColumn();

$pageTitle = $item['title'];

$statusClass = match ($item['status']) {
    'Dipesan' => 'badge-dipesan',
    'Terjual' => 'badge-terjual',
    default   => 'badge-tersedia',
};

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="home.php">Beranda</a></li>
            <li class="breadcrumb-item"><a href="category.php?slug=<?= htmlspecialchars($item['category_slug']) ?>"><?= htmlspecialchars($item['category_name']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($item['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">

        <!-- ── Left: Photo Gallery ──────────────────────────── -->
        <div class="col-md-7">
            <?php
            $mainPhoto = !empty($photos) ? $photos[0]['photo_path'] : null;
            ?>

            <!-- Main photo -->
            <div class="mb-3">
                <?php if ($mainPhoto): ?>
                    <img
                        id="mainPhoto"
                        src="<?= htmlspecialchars($mainPhoto) ?>"
                        alt="<?= htmlspecialchars($item['title']) ?>"
                        class="img-fluid rounded shadow-sm w-100"
                        style="max-height:420px; object-fit:cover; cursor:pointer;"
                        data-bs-toggle="modal"
                        data-bs-target="#lightboxModal"
                        onclick="openLightbox(this.src)"
                    >
                <?php else: ?>
                    <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="height:420px;">
                        <i class="bi bi-image fs-1"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Thumbnails -->
            <?php if (count($photos) > 1): ?>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($photos as $photo): ?>
                    <img
                        src="<?= htmlspecialchars($photo['photo_path']) ?>"
                        alt="Thumbnail"
                        class="rounded border"
                        style="width:72px; height:72px; object-fit:cover; cursor:pointer; opacity:<?= $photo['is_primary'] ? '1' : '0.65' ?>;"
                        onclick="switchPhoto(this)"
                    >
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Right: Item Info ──────────────────────────────── -->
        <div class="col-md-5">

            <!-- Title -->
            <h4 class="fw-bold mb-2"><?= htmlspecialchars($item['title']) ?></h4>

            <!-- Price -->
            <h5 class="fw-bold mb-3" style="color:var(--color-primary);">
                <?= formatPrice((float) $item['price']) ?>
            </h5>

            <!-- Status badge -->
            <p class="mb-3">
                <span class="<?= $statusClass ?>"><?= htmlspecialchars($item['status']) ?></span>
            </p>

            <!-- Category -->
            <p class="text-muted small mb-2">
                <i class="bi bi-tag me-1"></i>
                <?= htmlspecialchars($item['category_name']) ?>
            </p>

            <!-- Location -->
            <p class="text-muted small mb-2">
                <i class="bi bi-geo-alt me-1"></i>
                <?= htmlspecialchars($item['location']) ?>
            </p>

            <!-- Posted time -->
            <p class="text-muted small mb-3">
                <i class="bi bi-clock me-1"></i>
                <?= date('d M Y, H:i', strtotime($item['created_at'])) ?>
            </p>

            <!-- Description -->
            <div class="mb-4">
                <h6 class="fw-semibold mb-1">Deskripsi</h6>
                <p class="text-muted" style="white-space:pre-wrap;"><?= htmlspecialchars($item['description']) ?></p>
            </div>

            <!-- Seller info card -->
            <div class="card border-0 bg-light rounded-3 p-3 mb-3">
                <div class="d-flex align-items-center gap-3">
                    <?php if (!empty($item['seller_photo'])): ?>
                        <img
                            src="<?= htmlspecialchars($item['seller_photo']) ?>"
                            alt="<?= htmlspecialchars($item['seller_name']) ?>"
                            class="rounded-circle"
                            style="width:48px; height:48px; object-fit:cover;"
                        >
                    <?php else: ?>
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style="width:48px; height:48px;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                    <?php endif; ?>
                    <div class="flex-grow-1">
                        <div class="fw-semibold"><?= htmlspecialchars($item['seller_name']) ?></div>
                        <div class="stars-display small">
                            <?php
                            $rounded = round($avgRating);
                            for ($s = 1; $s <= 5; $s++):
                            ?>
                                <i class="bi bi-star-fill <?= $s <= $rounded ? '' : 'star-empty' ?>"></i>
                            <?php endfor; ?>
                            <span class="text-muted ms-1"><?= $avgRating > 0 ? number_format($avgRating, 1) : 'Belum ada rating' ?></span>
                        </div>
                    </div>
                    <a href="seller_detail.php?id=<?= (int) $item['seller_id'] ?>" class="btn btn-sm btn-outline-secondary">
                        Lihat Toko
                    </a>
                </div>
            </div>

            <!-- Wishlist button -->
            <button
                id="wishlistBtn"
                class="btn <?= $inWishlist ? 'btn-danger' : 'btn-outline-danger' ?> w-100 mb-2 d-flex align-items-center justify-content-center gap-2"
                onclick="toggleWishlist(<?= $id ?>)"
            >
                <i class="bi <?= $inWishlist ? 'bi-heart-fill' : 'bi-heart' ?>" id="wishlistIcon"></i>
                <span id="wishlistText"><?= $inWishlist ? 'Hapus dari Wishlist' : 'Simpan ke Wishlist' ?></span>
            </button>

            <!-- Chat Seller button -->
            <?php if ((int) $item['seller_id'] === (int) $currentUser['id']): ?>
                <div class="alert alert-info py-2 text-center small mb-2">
                    <i class="bi bi-info-circle me-1"></i>Ini barang Anda
                </div>
                <?php if ($item['status'] === 'Tersedia'): ?>
                    <div class="d-flex gap-2">
                        <a href="edit_item.php?id=<?= $id ?>" class="btn btn-outline-primary flex-grow-1">
                            <i class="bi bi-pencil me-1"></i>Edit Barang
                        </a>
                        <button class="btn btn-outline-danger" onclick="deleteItem(<?= $id ?>)" title="Hapus barang">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning py-2 text-center small mb-0">
                        <i class="bi bi-lock me-1"></i>Barang <?= htmlspecialchars($item['status']) ?> — tidak dapat diedit atau dihapus
                    </div>
                <?php endif; ?>
            <?php elseif ($item['status'] === 'Tersedia'): ?>
                <form method="POST" action="../api/chat_room_create.php" id="chatForm">
                    <input type="hidden" name="item_id" value="<?= $id ?>">
                    <button type="submit" class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-chat-dots"></i>
                        Chat Seller
                    </button>
                </form>
            <?php else: ?>
                <button class="btn btn-secondary w-100" disabled>
                    <i class="bi bi-chat-dots me-2"></i>
                    <?= $item['status'] === 'Dipesan' ? 'Barang Sedang Dipesan' : 'Barang Sudah Terjual' ?>
                </button>
            <?php endif; ?>

        </div>
    </div>
</main>

<!-- ── Lightbox Modal ────────────────────────────────────────── -->
<div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-2">
                <img id="lightboxImg" src="" alt="Foto barang" class="img-fluid rounded" style="max-height:80vh;">
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>

<script>
// Thumbnail switching
function switchPhoto(thumb) {
    const main = document.getElementById('mainPhoto');
    if (!main) return;

    // Reset all thumbnail opacities
    document.querySelectorAll('[onclick="switchPhoto(this)"]').forEach(function(t) {
        t.style.opacity = '0.65';
    });
    thumb.style.opacity = '1';
    main.src = thumb.src;
}

// Lightbox
function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
}

// Wishlist AJAX toggle
function toggleWishlist(itemId) {
    fetch('../api/wishlist_toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'item_id=' + itemId
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        const btn  = document.getElementById('wishlistBtn');
        const icon = document.getElementById('wishlistIcon');
        const text = document.getElementById('wishlistText');

        if (data.in_wishlist) {
            btn.className  = 'btn btn-danger w-100 mb-2 d-flex align-items-center justify-content-center gap-2';
            icon.className = 'bi bi-heart-fill';
            text.textContent = 'Hapus dari Wishlist';
        } else {
            btn.className  = 'btn btn-outline-danger w-100 mb-2 d-flex align-items-center justify-content-center gap-2';
            icon.className = 'bi bi-heart';
            text.textContent = 'Simpan ke Wishlist';
        }
    })
    .catch(function(err) {
        console.error('Wishlist toggle error:', err);
    });
}

// Chat form: POST then redirect to chat room
<?php if ((int) $item['seller_id'] !== (int) $currentUser['id'] && $item['status'] === 'Tersedia'): ?>
document.getElementById('chatForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('../api/chat_room_create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'item_id=<?= $id ?>'
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.room_id) {
            window.location.href = 'chat.php?room_id=' + data.room_id;
        }
    })
    .catch(function(err) {
        console.error('Chat room create error:', err);
    });
});
<?php endif; ?>

<?php if ((int) $item['seller_id'] === (int) $currentUser['id']): ?>
function deleteItem(itemId) {
    if (!confirm('Hapus barang ini? Tindakan ini tidak dapat dibatalkan.')) return;
    fetch('../api/delete_item.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'item_id=' + itemId
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            window.location.href = 'home.php';
        } else {
            alert(data.error || 'Gagal menghapus barang.');
        }
    })
    .catch(function() { alert('Terjadi kesalahan. Coba lagi.'); });
}
<?php endif; ?>
</script>
