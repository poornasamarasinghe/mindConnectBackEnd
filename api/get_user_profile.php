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
        $user_type = $_POST['user_type'] ?? null;

        if (!$user_id || !$user_type) {
            echo json_encode(["success" => false, "message" => "User ID and type required."]);
            return;
        }

        // Get basic user info from `user` table
        $stmt = $conn->prepare("SELECT username, profile_pic_url FROM user WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            echo json_encode(["success" => false, "message" => "User not found."]);
            return;
        }

        // Get display_name if doctor or counsellor
        $display_name = $user['username']; // fallback
        if ($user_type === 'doctor') {
            $stmt2 = $conn->prepare("SELECT display_name FROM doctor WHERE user_id = ?");
        } elseif ($user_type === 'counsellor') {
            $stmt2 = $conn->prepare("SELECT display_name FROM counsellor WHERE user_id = ?");
        }

        if (isset($stmt2)) {
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $row = $result2->fetch_assoc();
            if ($row && !empty($row['display_name'])) {
                $display_name = $row['display_name'];
            }
            $stmt2->close();
        }

        $conn->close();

        echo json_encode([
            "success" => true,
            "username" => $display_name,
            "profile_pic_url" => $user['profile_pic_url'] ?? ""
        ]);
    }
} catch (Exception $err) {
    echo json_encode(["success" => false, "message" => $err->getMessage()]);
}
