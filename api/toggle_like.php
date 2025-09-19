<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: *");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    return;
}

require_once '../database/dbconnection.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn = (new DbConnection())->getConnection();
$data = json_decode(file_get_contents("php://input"), true);
$post_id = intval($data['post_id'] ?? 0);
$user_id = intval($data['user_id'] ?? 0);


        if (!$post_id || !$user_id) {
            echo json_encode([
                "success" => false,
                "message" => "Post ID and User ID are required."
            ]);
            return;
        }

        // Check if user already liked this post
        $checkStmt = $conn->prepare("SELECT like_id FROM postlikes WHERE post_id = ? AND user_id = ?");
        $checkStmt->bind_param("ii", $post_id, $user_id);
        $checkStmt->execute();
        $checkStmt->store_result();

        $liked = false;
        if ($checkStmt->num_rows > 0) {
            // Unlike → delete
            $deleteStmt = $conn->prepare("DELETE FROM postlikes WHERE post_id = ? AND user_id = ?");
            $deleteStmt->bind_param("ii", $post_id, $user_id);
            $deleteStmt->execute();
            $deleteStmt->close();
            $liked = false;
        } else {
            // Like → insert
            $insertStmt = $conn->prepare("INSERT INTO postlikes (post_id, user_id) VALUES (?, ?)");
            $insertStmt->bind_param("ii", $post_id, $user_id);
            $insertStmt->execute();
            $insertStmt->close();
            $liked = true;
        }
        $checkStmt->close();

        // Get updated like count
        $countStmt = $conn->prepare("SELECT COUNT(*) AS total_likes FROM postlikes WHERE post_id = ?");
        $countStmt->bind_param("i", $post_id);
        $countStmt->execute();
        $result = $countStmt->get_result()->fetch_assoc();
        $countStmt->close();

        $conn->close();

        echo json_encode([
            "success" => true,
            "liked" => $liked,
            "total_likes" => intval($result['total_likes'])
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid request method."]);
    }
} catch (Exception $err) {
    echo json_encode(["success" => false, "message" => $err->getMessage()]);
}
