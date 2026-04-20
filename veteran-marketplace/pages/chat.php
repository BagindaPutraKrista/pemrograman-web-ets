<?php
require_once __DIR__ . '/../includes/auth_check.php';

$roomId = isset($_GET['room_id']) ? (int) $_GET['room_id'] : 0;

// Query all chat rooms for current user
$userId = (int) $currentUser['id'];
$stmt = $pdo->prepare("
    SELECT cr.id, cr.item_id, cr.buyer_id, cr.seller_id,
           i.title as item_title,
           CASE WHEN cr.buyer_id = ? THEN su.name ELSE bu.name END as other_name,
           CASE WHEN cr.buyer_id = ? THEN su.profile_photo ELSE bu.profile_photo END as other_photo,
           (SELECT message FROM chat_messages WHERE room_id = cr.id ORDER BY created_at DESC LIMIT 1) as last_message,
           (SELECT COUNT(*) FROM chat_messages WHERE room_id = cr.id AND sender_id != ? AND is_read = 0) as unread_count
    FROM chat_rooms cr
    JOIN items i ON i.id = cr.item_id
    JOIN users bu ON bu.id = cr.buyer_id
    JOIN users su ON su.id = cr.seller_id
    WHERE cr.buyer_id = ? OR cr.seller_id = ?
    ORDER BY (SELECT MAX(created_at) FROM chat_messages WHERE room_id = cr.id) DESC
");
$stmt->execute([$userId, $userId, $userId, $userId, $userId]);
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If a room is selected, verify user is a member
$activeRoom = null;
if ($roomId > 0) {
    foreach ($rooms as $r) {
        if ((int) $r['id'] === $roomId) {
            $activeRoom = $r;
            break;
        }
    }
    // If not found in user's rooms, reset
    if (!$activeRoom) {
        $roomId = 0;
    }
}

$pageTitle = 'Chat – Veteran Marketplace';
require_once __DIR__ . '/../includes/layout_head.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<!-- Pass current user id to JS -->
<input type="hidden" id="currentUserId" value="<?= $userId ?>">

<div class="container-fluid py-3" style="max-width: 1200px;">
    <div class="row g-0 border rounded bg-white" style="min-height: 80vh;">

        <!-- ── Left Column: Room List ──────────────────────────── -->
        <div class="col-md-4 col-lg-3 border-end d-flex flex-column" style="min-height:0;">
            <div class="p-3 border-bottom bg-light flex-shrink-0">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-chat-dots me-2"></i>Percakapan
                </h6>
            </div>

            <div class="overflow-auto flex-grow-1">
                <?php if (empty($rooms)): ?>
                    <div class="text-center text-muted py-5 px-3">
                        <i class="bi bi-chat-square-dots fs-2 d-block mb-2"></i>
                        <small>Belum ada percakapan</small>
                    </div>
                <?php else: ?>
                    <?php foreach ($rooms as $room):
                        $isActive = ((int) $room['id'] === $roomId);
                        $otherPhoto = $room['other_photo']
                            ? htmlspecialchars($room['other_photo'])
                            : null;
                    ?>
                    <a
                        href="chat.php?room_id=<?= (int) $room['id'] ?>"
                        class="d-flex align-items-center gap-3 p-3 border-bottom text-decoration-none room-link <?= $isActive ? 'active-room' : '' ?>"
                        style="color:inherit; background:<?= $isActive ? 'rgba(45,106,79,0.08)' : 'transparent' ?>;"
                    >
                        <!-- Avatar -->
                        <div class="flex-shrink-0">
                            <?php if ($otherPhoto): ?>
                                <img
                                    src="<?= $otherPhoto ?>"
                                    alt="<?= htmlspecialchars($room['other_name']) ?>"
                                    class="rounded-circle"
                                    style="width:42px;height:42px;object-fit:cover;"
                                >
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white"
                                     style="width:42px;height:42px;font-size:1.1rem;">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Info -->
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-semibold text-dark small text-truncate">
                                    <?= htmlspecialchars($room['other_name']) ?>
                                </span>
                                <?php if ((int) $room['unread_count'] > 0): ?>
                                    <span class="badge rounded-pill bg-danger ms-1 flex-shrink-0">
                                        <?= (int) $room['unread_count'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted small text-truncate">
                                <?= htmlspecialchars($room['item_title']) ?>
                            </div>
                            <?php if ($room['last_message']): ?>
                                <div class="text-muted small text-truncate" style="font-size:0.75rem;">
                                    <?= htmlspecialchars($room['last_message']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Right Column: Message Area ─────────────────────── -->
        <div class="col-md-8 col-lg-9 d-flex flex-column">

            <?php if (!$activeRoom): ?>
                <!-- No room selected placeholder -->
                <div class="d-flex flex-column align-items-center justify-content-center flex-grow-1 text-muted">
                    <i class="bi bi-chat-square-text fs-1 mb-3"></i>
                    <p class="mb-0">Pilih percakapan</p>
                </div>

            <?php else: ?>
                <!-- Chat header: user info only -->
                <div class="p-3 border-bottom bg-white d-flex align-items-center gap-3">
                    <?php
                    $otherPhoto = $activeRoom['other_photo']
                        ? htmlspecialchars($activeRoom['other_photo'])
                        : null;
                    // Fetch live item data
                    $liveItemStmt = $pdo->prepare("
                        SELECT i.title, i.price, i.status, ip.photo_path
                        FROM items i
                        LEFT JOIN item_photos ip ON ip.item_id = i.id AND ip.is_primary = 1
                        WHERE i.id = ?
                    ");
                    $liveItemStmt->execute([$activeRoom['item_id']]);
                    $liveItem = $liveItemStmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <?php if ($otherPhoto): ?>
                        <img src="<?= $otherPhoto ?>" alt="<?= htmlspecialchars($activeRoom['other_name']) ?>"
                             class="rounded-circle flex-shrink-0" style="width:40px;height:40px;object-fit:cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width:40px;height:40px;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                    <?php endif; ?>
                    <div class="fw-semibold flex-grow-1"><?= htmlspecialchars($activeRoom['other_name']) ?></div>
                    <?php
                    $itemStatus = $liveItem['status'] ?? 'Tersedia';
                    $txStmt = $pdo->prepare("SELECT id, status FROM transactions WHERE item_id = ? LIMIT 1");
                    $txStmt->execute([$activeRoom['item_id']]);
                    $existingTx = $txStmt->fetch(PDO::FETCH_ASSOC);
                    $isSeller = ((int) $activeRoom['seller_id'] === $userId);
                    $isBuyer  = ((int) $activeRoom['buyer_id'] === $userId);
                    ?>
                    <?php if ($isSeller && $itemStatus === 'Tersedia' && !$existingTx): ?>
                        <button class="btn btn-warning btn-sm fw-semibold flex-shrink-0"
                                onclick="confirmDeal(<?= (int) $activeRoom['item_id'] ?>, <?= (int) $activeRoom['buyer_id'] ?>)">
                            <i class="bi bi-handshake me-1"></i>Deal
                        </button>
                    <?php elseif ($existingTx): ?>
                        <span class="badge flex-shrink-0 bg-<?= $existingTx['status'] === 'Selesai' ? 'success' : ($existingTx['status'] === 'Diproses' ? 'info text-dark' : 'warning text-dark') ?> px-3 py-2">
                            <?= htmlspecialchars($existingTx['status']) ?>
                        </span>
                    <?php elseif ($itemStatus === 'Terjual'): ?>
                        <span class="badge flex-shrink-0 bg-secondary px-3 py-2">Terjual</span>
                    <?php endif; ?>
                </div>

                <!-- Item info bar (below header, Shopee/Tokopedia style) -->
                <a href="item_detail.php?id=<?= (int) $activeRoom['item_id'] ?>"
                   class="d-flex align-items-center gap-3 px-3 py-2 border-bottom text-decoration-none"
                   style="background:#f8f9fa;">
                    <?php if (!empty($liveItem['photo_path'])): ?>
                        <img src="<?= htmlspecialchars($liveItem['photo_path']) ?>"
                             alt="<?= htmlspecialchars($liveItem['title'] ?? '') ?>"
                             class="rounded flex-shrink-0"
                             style="width:52px;height:52px;object-fit:cover;border:1px solid #dee2e6;">
                    <?php else: ?>
                        <div class="rounded flex-shrink-0 bg-light d-flex align-items-center justify-content-center text-muted"
                             style="width:52px;height:52px;border:1px solid #dee2e6;">
                            <i class="bi bi-image"></i>
                        </div>
                    <?php endif; ?>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="text-dark small fw-semibold text-truncate">
                            <?= htmlspecialchars($liveItem['title'] ?? $activeRoom['item_title']) ?>
                        </div>
                        <?php if (!empty($liveItem['price'])): ?>
                            <div class="fw-bold small" style="color:var(--color-primary);">
                                <?= formatPrice((float) $liveItem['price']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <i class="bi bi-chevron-right text-muted flex-shrink-0"></i>
                </a>

                <!-- Messages area -->
                <div
                    id="messagesArea"
                    class="flex-grow-1 overflow-auto p-3"
                    style="height: 60vh;"
                >
                    <!-- Messages loaded by JS -->
                </div>

                <!-- Input area -->
                <div class="p-3 border-top bg-light">
                    <div class="d-flex gap-2">
                        <textarea
                            id="messageInput"
                            class="form-control"
                            rows="2"
                            placeholder="Tulis pesan..."
                            style="resize:none;"
                        ></textarea>
                        <button id="sendBtn" class="btn btn-primary px-3 align-self-end">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$extraJs = '<script src="../assets/js/chat.js"></script>';
if ($activeRoom) {
    $extraJs = '<script>const INITIAL_ROOM_ID = ' . (int) $roomId . ';</script>' . "\n" . $extraJs;
} else {
    $extraJs = '<script>const INITIAL_ROOM_ID = null;</script>' . "\n" . $extraJs;
}
$extraJs .= <<<'JS'
<script>
function confirmDeal(itemId, buyerId) {
    if (!confirm('Konfirmasi deal dengan pembeli ini? Status barang akan berubah menjadi "Dipesan" dan transaksi akan dibuat.')) return;
    fetch('../api/deal_create.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'item_id=' + itemId + '&buyer_id=' + buyerId
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            // Show 5-second toast for seller, then reload after it finishes
            if (data.seller_message && typeof showToast === 'function') {
                showToast(data.seller_message, 'success', 5000);
                setTimeout(function() { location.reload(); }, 5200);
            } else {
                location.reload();
            }
        } else {
            alert(data.error || 'Gagal membuat deal.');
        }
    })
    .catch(function() { alert('Terjadi kesalahan. Coba lagi.'); });
}
</script>
JS;
require_once __DIR__ . '/../includes/layout_foot.php';
?>
