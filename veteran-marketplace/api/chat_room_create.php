<?php
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$item_id = (int) ($_POST['item_id'] ?? 0);
if ($item_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'item_id tidak valid']);
    exit;
}

// Get item to find seller_id
$stmt = $pdo->prepare("SELECT id, seller_id FROM items WHERE id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    http_response_code(404);
    echo json_encode(['error' => 'Item tidak ditemukan']);
    exit;
}

$buyer_id  = (int) $currentUser['id'];
$seller_id = (int) $item['seller_id'];

// Prevent buyer from chatting with themselves
if ($buyer_id === $seller_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Tidak dapat membuat chat dengan diri sendiri']);
    exit;
}

// INSERT IGNORE to handle UNIQUE(buyer_id, seller_id, item_id)
$insertStmt = $pdo->prepare("
    INSERT IGNORE INTO chat_rooms (buyer_id, seller_id, item_id)
    VALUES (?, ?, ?)
");
$insertStmt->execute([$buyer_id, $seller_id, $item_id]);

// SELECT the room id
$selectStmt = $pdo->prepare("
    SELECT id FROM chat_rooms
    WHERE buyer_id = ? AND seller_id = ? AND item_id = ?
");
$selectStmt->execute([$buyer_id, $seller_id, $item_id]);
$room = $selectStmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal membuat chat room']);
    exit;
}

echo json_encode(['room_id' => (int) $room['id']]);
