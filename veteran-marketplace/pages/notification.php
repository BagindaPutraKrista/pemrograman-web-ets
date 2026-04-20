<?php
require_once __DIR__ . '/../includes/auth_check.php';

// Query all notifications for current user
$stmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$currentUser['id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark all as read on page load
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")
    ->execute([$currentUser['id']]);

$pageTitle = 'Notifikasi – Veteran Marketplace';

require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="container py-4" style="max-width: 720px;">
    <h5 class="fw-semibold mb-4">
        <i class="bi bi-bell-fill me-2"></i>Notifikasi
    </h5>

    <?php if (empty($notifications)): ?>
        <!-- Empty state -->
        <div class="text-center py-5 text-muted">
            <i class="bi bi-bell-slash fs-1 d-block mb-3"></i>
            <p class="mb-0 fs-5">Tidak ada notifikasi</p>
            <p class="small mt-1">Notifikasi akan muncul di sini saat ada aktivitas baru</p>
        </div>
    <?php else: ?>
        <div class="list-group shadow-sm">
            <?php foreach ($notifications as $notif):
                // Icon based on type
                $icon = match ($notif['type']) {
                    'chat'        => 'bi-chat-dots text-primary',
                    'transaction' => 'bi-arrow-left-right text-warning',
                    'review'      => 'bi-star text-success',
                    default       => 'bi-bell text-secondary',
                };
                $bgClass = $notif['is_read'] ? '' : 'bg-light';
                $link    = !empty($notif['link']) ? htmlspecialchars($notif['link']) : '#';
            ?>
            <a
                href="<?= $link ?>"
                class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3 <?= $bgClass ?>"
            >
                <!-- Icon -->
                <div class="flex-shrink-0 mt-1">
                    <i class="bi <?= $icon ?> fs-5"></i>
                </div>

                <!-- Content -->
                <div class="flex-grow-1 min-width-0">
                    <p class="mb-1 <?= $notif['is_read'] ? 'text-muted' : 'fw-semibold' ?>">
                        <?= htmlspecialchars($notif['message']) ?>
                    </p>
                    <small class="text-muted d-flex align-items-center gap-1">
                        <i class="bi bi-clock"></i>
                        <?= date('d M Y, H:i', strtotime($notif['created_at'])) ?>
                    </small>
                </div>

                <!-- Unread dot -->
                <?php if (!$notif['is_read']): ?>
                <div class="flex-shrink-0 mt-2">
                    <span class="badge rounded-pill bg-primary" style="width:8px; height:8px; padding:0;">&nbsp;</span>
                </div>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/layout_foot.php'; ?>
