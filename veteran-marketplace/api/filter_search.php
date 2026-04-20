<?php
/**
 * Filter Search API
 * GET: q (keyword), sort (relevance/newest/price_high/price_low), category_id
 * Returns JSON: { items: [...], total: N }
 */

require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$q           = trim($_GET['q'] ?? '');
$sort        = $_GET['sort'] ?? 'newest';
$category_id = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;

// Build dynamic query
$params = [];
$where  = ["i.status = 'Tersedia'"];

if ($q !== '') {
    $where[]  = "(i.title LIKE ? OR i.description LIKE ?)";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}

if ($category_id > 0) {
    $where[]  = "i.category_id = ?";
    $params[] = $category_id;
}

$orderBy = match ($sort) {
    'price_high' => 'ORDER BY i.price DESC',
    'price_low'  => 'ORDER BY i.price ASC',
    'newest'     => 'ORDER BY i.created_at DESC',
    default      => 'ORDER BY i.created_at DESC',
};

$sql = "
    SELECT i.id, i.title, i.price, i.location, i.status, i.created_at,
           ip.photo_path, u.name as seller_name
    FROM items i
    LEFT JOIN item_photos ip ON ip.item_id = i.id AND ip.is_primary = 1
    LEFT JOIN users u ON u.id = i.seller_id
    WHERE " . implode(' AND ', $where) . "
    $orderBy
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'items' => $items,
    'total' => count($items),
]);
