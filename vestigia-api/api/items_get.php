<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_helper.php';

$pdo = getDbConnection();
requireAuth($pdo);

$id = $_GET["id"] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Item id is required."]);
    exit;
}

$stmt = $pdo->prepare("SELECT fi.*, u.first_name, u.last_name
                        FROM found_items fi
                        LEFT JOIN users u ON u.id = fi.reported_by
                        WHERE fi.id = :id");
$stmt->execute(["id" => $id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Item not found."]);
    exit;
}

$item['image_url'] = storagePublicUrl($item['image_path']);

echo json_encode(["success" => true, "item" => $item]);