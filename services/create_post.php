<?php
// create_post.php

// --- CORS ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// --- Always JSON response ---
header("Content-Type: application/json; charset=utf-8");
ini_set('display_errors', 0);

// --- Include DB & Azure Helper ---
require_once '../database/dbconnection.php';
require_once __DIR__ . '/../Utilities/AzureHelper.php';

// --- Connect to DB ---
$db = new DbConnection();
$conn = $db->getConnection();
if (!$conn) {
    echo json_encode(["status" => "error", "message" => "DB connection failed"]);
    exit;
}
$conn->set_charset("utf8mb4");

// --- Read form-data fields ---
$user_id  = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$title    = isset($_POST['title']) ? trim($_POST['title']) : "";
$content  = isset($_POST['content']) ? trim($_POST['content']) : "";
$category = isset($_POST['category']) ? trim($_POST['category']) : "";
$created  = date("Y-m-d H:i:s");

// --- Validate required fields ---
if ($user_id <= 0 || empty($title) || empty($content) || empty($category)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing or invalid fields"]);
    exit;
}

// --- Handle optional file upload ---
$post_file_url = null;
if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    $filename = uniqid() . "_" . basename($file['name']);

    $azure = new AzureHelper();
    $containerName = "postfiles";
    $uploadResult = $azure->uploadFile($containerName, $file["tmp_name"], $filename);

    if (!$uploadResult['success']) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "File upload failed: " . $uploadResult['message']]);
        exit;
    }

    $post_file_url = $uploadResult['url'];
}

// --- Insert post into DB ---
$sql = "INSERT INTO post (user_id, title, content, category, post_file_url, created_at) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to prepare statement"]);
    exit;
}

$stmt->bind_param("isssss", $user_id, $title, $content, $category, $post_file_url, $created);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Post created successfully",
        "post_id" => $stmt->insert_id,
        "post_file_url" => $post_file_url
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to insert post: " . $stmt->error]);
}

$stmt->close();
$conn->close();
