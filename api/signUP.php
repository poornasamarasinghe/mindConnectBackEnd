<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once '../database/dbconnection.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn = (new DbConnection())->getConnection();

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            echo json_encode(["success" => false, "message" => "Email and password required."]);
            return;
        }

       // $stmt = $conn->prepare("SELECT user_id, password_hash, user_type, account_status FROM user WHERE email = ?");
       $result = $conn->query("SELECT * FROM user WHERE email = '$email'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    error_log("Found user: " . print_r($row, true));
} else {
    error_log("No user found for email: $email");
}

       $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            if (!password_verify($password, $row['password_hash'])) {
                echo json_encode(["success" => false, "message" => "Incorrect password."]);
                return;
            }

            if ($row['account_status'] !== 'verified') {
                echo json_encode(["success" => false, "message" => "Account not verified."]);
                return;
            }

            echo json_encode([
                "success" => true,
                "message" => "Login successful.",
                "user" => [
                    "user_id" => $row['user_id'],
                    "user_type" => $row['user_type'],
                    "email" => $email
                ]
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "User not found."]);
        }

        $conn->close();
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server error: " . $e->getMessage()]);
}
?>
