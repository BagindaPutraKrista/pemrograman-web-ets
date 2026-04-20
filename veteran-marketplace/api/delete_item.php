<?php
/**
 * delete_item.php — Delete an item owned by the current user.
 * POST: item_id
 */
require_once __DIR__ . '/../includes/auth_check.php';

// Ensure clean JSON output — suppress any warnings that could corrupt JSON
error_reporting(0);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$itemId  = (int) ($_POST['item_id'] ?? 0);
$userId  = (int) $currentUser['id'];

if ($itemId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'item_id tidak valid']);
    exit;
}

try {
    // Verify ownership and check status
    $stmt = $pdo->prepare("SELECT id, seller_id, status FROM items WHERE id = ? LIMIT 1");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item || (int) $item['seller_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'Akses ditolak']);
        exit;
    }

    // Block deletion for sold or ordered items
    if (in_array($item['status'], ['Terjual', 'Dipesan'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Barang yang sudah ' . $item['status'] . ' tidak dapat dihapus.']);
        exit;
    }

    // Delete photo files from disk
    $photoStmt = $pdo->prepare("SELECT photo_path FROM item_photos WHERE item_id = ?");
    $photoStmt->execute([$itemId]);
    foreach ($photoStmt->fetchAll(PDO::FETCH_ASSOC) as $photo) {
        $fullPath = __DIR__ . '/../' . ltrim($photo['photo_path'], '../');
        if (file_exists($fullPath)) @unlink($fullPath);
    }

    // Delete item (cascades to item_photos, wishlists, chat_rooms, etc.)
    $pdo->prepare("DELETE FROM items WHERE id = ? AND seller_id = ?")->execute([$itemId, $userId]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menghapus barang: ' . $e->getMessage()]);
}
