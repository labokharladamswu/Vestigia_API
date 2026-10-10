<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_helper.php';

$pdo = getDbConnection();
requireAuth($pdo);

$search = trim($_GET["search"] ?? "");
$filter = trim($_GET["filter"] ?? "all");

$sql = "SELECT fi.*, u.first_name, u.last_name
        FROM found_items fi
        LEFT JOIN users u ON u.id = fi.reported_by
        WHERE 1=1";
$params = [];

if ($search !== "") {
    $sql .= " AND (fi.item_name ILIKE :search OR fi.location ILIKE :search OR fi.description ILIKE :search)";
    $params["search"] = "%$search%";
}

if ($filter === "today") {
    $sql .= " AND fi.date_found = CURRENT_DATE";
} elseif ($filter === "week") {
    $sql .= " AND fi.date_found >= CURRENT_DATE - INTERVAL '7 days' AND fi.date_found < CURRENT_DATE";
} elseif ($filter === "older") {
    $sql .= " AND fi.date_found < CURRENT_DATE - INTERVAL '7 days'";
}

$sql .= " ORDER BY fi.date_found DESC, fi.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build a full image URL for each item so Android can load it directly

foreach ($items as &$item) {
    $item['image_url'] = storagePublicUrl($item['image_path']);
}

echo json_encode(["success" => true, "items" => $items]);