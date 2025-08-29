<?php
// create_post.php

// --- CORS (handle preflight too) ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// --- Always return JSON only ---
header("Content-Type: application/json; charset=utf-8");

// Optional: avoid PHP warnings breaking JSON
ini_set('display_errors', 0);
error_reporting(0);

require_once '../database/dbconnection.php';
header("Content-Type: application/json; charset=utf-8");
ini_set('display_errors', 0); // stop warnings from breaking JSON

$db = new DbConnection();
$conn = $db->getConnection();

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "DB connection failed"]);
    exit;
}

$conn->set_charset("utf8mb4");

//$conn->set_charset("utf8mb4");

// --- Read JSON body ---
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON body"]);
    exit;
}

// --- Validate ---
$required = ["user_id", "title", "content", "category"];
foreach ($required as $field) {
    if (!array_key_exists($field, $data)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing field: $field"]);
        exit;
    }
}
$user_id  = (int)$data["user_id"];
$title    = trim((string)$data["title"]);
$content  = trim((string)$data["content"]);
$category = trim((string)$data["category"]);
$created  = date("Y-m-d H:i:s");

if ($user_id <= 0 || $title === "" || $content === "" || $category === "") {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Empty or invalid fields"]);
    exit;
}

// --- Insert using prepared statement ---
$sql = "INSERT INTO Post (user_id, title, content, category, created_at)
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Prepare failed"]);
    exit;
}
$stmt->bind_param("issss", $user_id, $title, $content, $category, $created);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Post created",
        "post_id" => $stmt->insert_id
    ]);
} else {
    // If you have a FK on user_id and it doesn't exist, this will fail
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Insert failed"]);
}

$stmt->close();
$conn->close();
