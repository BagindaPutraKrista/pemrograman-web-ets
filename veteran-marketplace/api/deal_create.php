<?php
/**
 * deal_create.php — Seller confirms deal with buyer.
 * Creates a transaction (status=Menunggu), sets item status to Dipesan,
 * and notifies both parties.
 */
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$itemId  = (int) ($_POST['item_id']  ?? 0);
$buyerId = (int) ($_POST['buyer_id'] ?? 0);
$sellerId = (int) $currentUser['id'];

if ($itemId <= 0 || $buyerId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter tidak valid']);
    exit;
}

// Verify caller is the seller of this item
$itemStmt = $pdo->prepare("SELECT id, title, seller_id, status FROM items WHERE id = ? LIMIT 1");
$itemStmt->execute([$itemId]);
$item = $itemStmt->fetch(PDO::FETCH_ASSOC);

if (!$item || (int) $item['seller_id'] !== $sellerId) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

if ($item['status'] !== 'Tersedia') {
    http_response_code(400);
    echo json_encode(['error' => 'Barang sudah tidak tersedia']);
    exit;
}

// Check no existing transaction
$txCheck = $pdo->prepare("SELECT id FROM transactions WHERE item_id = ? LIMIT 1");
$txCheck->execute([$itemId]);
if ($txCheck->fetch()) {
    http_response_code(400);
    echo json_encode(['error' => 'Transaksi sudah ada untuk barang ini']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Create transaction
    $pdo->prepare("
        INSERT INTO transactions (item_id, buyer_id, seller_id, status)
        VALUES (?, ?, ?, 'Menunggu')
    ")->execute([$itemId, $buyerId, $sellerId]);

    // Update item status to Dipesan
    $pdo->prepare("UPDATE items SET status = 'Dipesan' WHERE id = ?")
        ->execute([$itemId]);

    $txLink = '../pages/transaction_history.php';

    // Notify buyer
    createNotification(
        $pdo, $buyerId, 'transaction',
        'Penjual menyetujui deal untuk "' . $item['title'] . '". Cek riwayat transaksi Anda.',
        $txLink
    );
    // Notify seller
    createNotification(
        $pdo, $sellerId, 'transaction',
        'Deal untuk "' . $item['title'] . '" berhasil dibuat. Menunggu konfirmasi pembeli.',
        $txLink
    );

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'seller_message' => 'Deal berhasil! Silakan ke menu Transaksi untuk memproses pesanan.',
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Gagal membuat deal: ' . $e->getMessage()]);
}
