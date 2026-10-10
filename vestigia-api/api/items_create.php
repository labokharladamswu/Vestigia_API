<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_helper.php';

$pdo = getDbConnection();
$session = requireAuth($pdo);

// Now expects multipart/form-data: item_name, category, location, date_found, description, image (file, optional)
$itemName    = trim($_POST['item_name'] ?? '');
$category    = trim($_POST['category'] ?? '');
$location    = trim($_POST['location'] ?? '');
$dateFound   = trim($_POST['date_found'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($itemName === '' || $location === '' || $dateFound === '') {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Item name, location, and date found are required."]);
    exit;
}

$imagePath = handleImageUpload('image', 'item_');

$stmt = $pdo->prepare("INSERT INTO found_items (item_name, description, category, location, date_found, status, image_path, reported_by)
                        VALUES (:name, :desc, :cat, :loc, :date, 'Unclaimed', :img, :uid) RETURNING id");
$stmt->execute([
    "name" => $itemName,
    "desc" => $description,
    "cat"  => $category,
    "loc"  => $location,
    "date" => $dateFound,
    "img"  => $imagePath,
    "uid"  => $session["user_id"]
]);
$newId = $stmt->fetchColumn();

echo json_encode(["success" => true, "message" => "Item added successfully.", "item_id" => $newId]);