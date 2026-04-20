<?php
/**
 * Wishlist Toggle API
 * POST: item_id (int)
 * Returns JSON: { in_wishlist: bool }
 */

require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$item_id = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;

if ($item_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid item_id']);
    exit;
}

$user_id = (int) $currentUser['id'];

// Check if item exists in wishlist
$stmt = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ? AND item_id = ?");
$stmt->execute([$user_id, $item_id]);
$existing = $stmt->fetch();

if ($existing) {
    // Remove from wishlist
    $del = $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND item_id = ?");
    $del->execute([$user_id, $item_id]);
    echo json_encode(['in_wishlist' => false]);
} else {
    // Add to wishlist (INSERT IGNORE handles race conditions / duplicate key)
    $ins = $pdo->prepare("INSERT IGNORE INTO wishlists (user_id, item_id) VALUES (?, ?)");
    $ins->execute([$user_id, $item_id]);
    echo json_encode(['in_wishlist' => true]);
}
