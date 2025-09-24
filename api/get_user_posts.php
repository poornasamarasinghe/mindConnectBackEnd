<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: *");

require_once '../database/dbconnection.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn = (new DbConnection())->getConnection();

        $user_id = $_POST['user_id'] ?? null;

        if (!$user_id) {
            echo json_encode(["success" => false, "message" => "User ID required."]);
            return;
        }

        // Select all necessary columns including font, background color, and font size
        $stmt = $conn->prepare("
            SELECT 
                post_id, 
                title, 
                content, 
                category, 
                post_file_url, 
                font, 
                bg_color, 
                font_size, 
                created_at 
            FROM post 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }

        $stmt->close();
        $conn->close();

        echo json_encode([
            "success" => true,
            "posts" => $posts
        ]);
    }
} catch (Exception $err) {
    echo json_encode(["success" => false, "message" => $err->getMessage()]);
}
