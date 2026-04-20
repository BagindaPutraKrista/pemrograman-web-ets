<?php
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$roomId  = (int) ($_POST['room_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($roomId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'room_id tidak valid']);
    exit;
}

if ($message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Pesan tidak boleh kosong']);
    exit;
}

$userId = (int) $currentUser['id'];

// Verify user is a member of the room
$checkStmt = $pdo->prepare(
    "SELECT id, buyer_id, seller_id FROM chat_rooms WHERE id = ? AND (buyer_id = ? OR seller_id = ?)"
);
$checkStmt->execute([$roomId, $userId, $userId]);
$room = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

// Insert message
$insertStmt = $pdo->prepare(
    "INSERT INTO chat_messages (room_id, sender_id, message) VALUES (?, ?, ?)"
);
$insertStmt->execute([$roomId, $userId, $message]);
$msgId = (int) $pdo->lastInsertId();

// Fetch the inserted message
$fetchStmt = $pdo->prepare(
    "SELECT id, sender_id, message, created_at FROM chat_messages WHERE id = ?"
);
$fetchStmt->execute([$msgId]);
$msg = $fetchStmt->fetch(PDO::FETCH_ASSOC);

// Determine the other user's ID
$otherUserId = ((int) $room['buyer_id'] === $userId)
    ? (int) $room['seller_id']
    : (int) $room['buyer_id'];

// Create notification for the other user
$notifLink = '../pages/chat.php?room_id=' . $roomId;
createNotification(
    $pdo,
    $otherUserId,
    'chat',
    'Pesan baru dari ' . $currentUser['name'],
    $notifLink
);

echo json_encode([
    'success' => true,
    'message' => [
        'id'         => (int) $msg['id'],
        'sender_id'  => (int) $msg['sender_id'],
        'message'    => $msg['message'],
        'created_at' => $msg['created_at'],
        'is_mine'    => true,
        'time'       => date('H:i', strtotime($msg['created_at'])),
    ],
]);
