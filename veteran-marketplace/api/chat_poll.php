<?php
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$roomId = (int) ($_GET['room_id'] ?? 0);
$lastId = (int) ($_GET['last_id'] ?? 0);

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

// Fetch new messages
$msgStmt = $pdo->prepare(
    "SELECT id, sender_id, message, created_at
     FROM chat_messages
     WHERE room_id = ? AND id > ?
     ORDER BY id ASC"
);
$msgStmt->execute([$roomId, $lastId]);
$rows = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

$messages = [];
$newLastId = $lastId;

foreach ($rows as $row) {
    $messages[] = [
        'id'        => (int) $row['id'],
        'sender_id' => (int) $row['sender_id'],
        'message'   => $row['message'],
        'created_at'=> $row['created_at'],
        'is_mine'   => ((int) $row['sender_id'] === $userId),
        'time'      => date('H:i', strtotime($row['created_at'])),
    ];
    $newLastId = (int) $row['id'];
}

echo json_encode([
    'messages' => $messages,
    'last_id'  => $newLastId,
]);
