<?php
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$counts = getUnreadCounts($pdo, (int) $currentUser['id']);

echo json_encode([
    'chat'  => $counts['chat'],
    'notif' => $counts['notif'],
]);
