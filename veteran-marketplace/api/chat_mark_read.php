<?php
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$roomId = (int) ($_POST['room_id'] ?? 0);

if ($roomId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'room_id tidak valid']);
    exit;
}

$userId = (int) $currentUser['id'];

// Verify user is a member of the room
$checkStmt = $pdo->prepare(
    "SELECT id FROM chat_rooms WHERE id = ? AND (buyer_id = ? OR seller_id = ?)"
);
$checkStmt->execute([$roomId, $userId, $userId]);
if (!$checkStmt->fetch()) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

// Mark all messages from the other user as read
$updateStmt = $pdo->prepare(
    "UPDATE chat_messages SET is_read = 1 WHERE room_id = ? AND sender_id != ?"
);
$updateStmt->execute([$roomId, $userId]);

echo json_encode(['success' => true]);
