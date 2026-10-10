<?php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_helper.php';

$pdo = getDbConnection();
$session = requireAuth($pdo);

$photoPath = handleImageUpload('photo', 'user_');
if ($photoPath === null) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "No photo was uploaded."]);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET photo_path = :photo WHERE id = :id");
$stmt->execute(["photo" => $photoPath, "id" => $session['user_id']]);

echo json_encode(["success" => true, "message" => "Profile photo updated.", "photo_url" => storagePublicUrl($photoPath)]);
