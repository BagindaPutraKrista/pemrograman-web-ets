<?php
/**
 * Item card component.
 * Requires: $item (array) with keys:
 *   id, title, price, location, status, photo_path, seller_name, created_at
 */

$statusClass = match ($item['status'] ?? 'Tersedia') {
    'Dipesan' => 'badge-dipesan',
    'Terjual' => 'badge-terjual',
    default   => 'badge-tersedia',
};
?>
<a href="../pages/item_detail.php?id=<?= (int) $item['id'] ?>" class="text-decoration-none">
    <div class="card item-card h-100 border-0 shadow-sm">

        <!-- Item photo -->
        <div class="position-relative">
            <?php if (!empty($item['photo_path'])): ?>
                <img
                    src="<?= htmlspecialchars($item['photo_path']) ?>"
                    alt="<?= htmlspecialchars($item['title']) ?>"
                    class="card-img-top"
                    style="height:180px; object-fit:cover;"
                >
            <?php else: ?>
                <div
                    class="card-img-top d-flex align-items-center justify-content-center bg-light text-muted"
                    style="height:180px;"
                >
                    <i class="bi bi-image fs-1"></i>
                </div>
            <?php endif; ?>

            <!-- Status badge -->
            <span class="position-absolute top-0 end-0 m-2 <?= $statusClass ?>">
                <?= htmlspecialchars($item['status'] ?? 'Tersedia') ?>
            </span>
        </div>

        <!-- Card body -->
        <div class="card-body p-3">
            <!-- Title (max 2 lines) -->
            <h6
                class="card-title mb-1 text-dark"
                style="display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"
            >
                <?= htmlspecialchars($item['title']) ?>
            </h6>

            <!-- Price -->
            <p class="fw-bold text-primary mb-2" style="color:var(--color-primary)!important;">
                <?= formatPrice((float) $item['price']) ?>
            </p>

            <!-- Location -->
            <p class="text-muted small mb-1 d-flex align-items-center gap-1">
                <i class="bi bi-geo-alt"></i>
                <?= htmlspecialchars($item['location']) ?>
            </p>

            <!-- Posted time -->
            <p class="text-muted small mb-0 d-flex align-items-center gap-1">
                <i class="bi bi-clock"></i>
                <?= date('d M Y, H:i', strtotime($item['created_at'])) ?>
            </p>
        </div>

    </div>
</a>
