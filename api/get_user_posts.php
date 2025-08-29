<?php
require_once '../database/dbconnection.php';

header("Access-Control-Allow-Origin: *"); // or restrict to your frontend origin
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");


// Get request body
$data = json_decode(file_get_contents("php://input"), true);
$user_id = intval($data['user_id'] ?? 0);

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Invalid user_id"]);
    exit;
}

try {
    $db = new DbConnection();
    $conn = $db->getConnection();

    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "Database connection failed"]);
        exit;
    }

    // ✅ Select only the logged-in user's posts
    $sql = "SELECT post_id, title, content, category, created_at 
            FROM Post 
            WHERE user_id = ? 
            ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }

    if (count($posts) > 0) {
        echo json_encode(["status" => "success", "posts" => $posts]);
    } else {
        echo json_encode(["status" => "success", "posts" => [], "message" => "No posts found"]);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
