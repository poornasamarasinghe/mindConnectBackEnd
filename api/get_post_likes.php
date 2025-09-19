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
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $conn = (new DbConnection())->getConnection();

        $post_id = intval($_GET['post_id'] ?? 0);
        $user_id = intval($_GET['user_id'] ?? 0);

        if (!$post_id) {
            echo json_encode(["success" => false, "message" => "Post ID required."]);
            return;
        }

        // Get total likes
        $countStmt = $conn->prepare("SELECT COUNT(*) AS total_likes FROM postlikes WHERE post_id = ?");
        $countStmt->bind_param("i", $post_id);
        $countStmt->execute();
        $result = $countStmt->get_result()->fetch_assoc();
        $countStmt->close();

        $totalLikes = intval($result['total_likes']);
        $liked = false;

        if ($user_id) {
            $checkStmt = $conn->prepare("SELECT like_id FROM postlikes WHERE post_id = ? AND user_id = ?");
            $checkStmt->bind_param("ii", $post_id, $user_id);
            $checkStmt->execute();
            $checkStmt->store_result();
            $liked = $checkStmt->num_rows > 0;
            $checkStmt->close();
        }

        $conn->close();

        echo json_encode([
            "success" => true,
            "liked" => $liked,
            "total_likes" => $totalLikes
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid request method."]);
    }
} catch (Exception $err) {
    echo json_encode(["success" => false, "message" => $err->getMessage()]);
}
