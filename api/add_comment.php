<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

require_once '../database/dbconnection.php';

try {
    $conn = (new DbConnection())->getConnection();

    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['post_id'], $data['user_id'], $data['content'])) {
        throw new Exception("Missing required fields");
    }

    $post_id = intval($data['post_id']);
    $user_id = intval($data['user_id']);
    $content = trim($data['content']);

    if ($content === "") {
        throw new Exception("Comment cannot be empty");
    }

    $sql = "INSERT INTO comment (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $post_id, $user_id, $content);
    $stmt->execute();

    echo json_encode([
        "success" => true,
        "message" => "Comment added successfully",
        "comment_id" => $stmt->insert_id
    ]);

    $stmt->close();
    $conn->close();

} catch (Exception $err) {
    echo json_encode([
        "success" => false,
        "message" => $err->getMessage()
    ]);
}
