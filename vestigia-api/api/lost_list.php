<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_helper.php';

$pdo = getDbConnection();
requireAuth($pdo);

$search = trim($_GET["search"] ?? "");
$filter = trim($_GET["filter"] ?? "all");

$sql = "SELECT li.*, u.first_name, u.last_name
        FROM lost_items li
        LEFT JOIN users u ON u.id = li.reported_by
        WHERE 1=1";
$params = [];

if ($search !== "") {
    $sql .= " AND (li.item_name ILIKE :search OR li.last_seen_location ILIKE :search OR li.description ILIKE :search)";
    $params["search"] = "%$search%";
}

if ($filter === "today") {
    $sql .= " AND li.date_lost = CURRENT_DATE";
} elseif ($filter === "week") {
    $sql .= " AND li.date_lost >= CURRENT_DATE - INTERVAL '7 days' AND li.date_lost < CURRENT_DATE";
} elseif ($filter === "older") {
    $sql .= " AND li.date_lost < CURRENT_DATE - INTERVAL '7 days'";
}

$sql .= " ORDER BY li.date_lost DESC, li.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);


foreach ($items as &$item) {
    $item['image_url'] = storagePublicUrl($item['image_path']);
}

echo json_encode(["success" => true, "items" => $items]);