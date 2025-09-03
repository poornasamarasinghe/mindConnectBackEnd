<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once '../database/dbconnection.php';
$conn = (new DbConnection())->getConnection();

// Check DB connection
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit();
}

// Receive POST data
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// Sanitize input
$email = filter_var($email, FILTER_SANITIZE_EMAIL);

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Missing email or password."]);
    exit();
}

// Fetch user from database
$stmt = $conn->prepare("SELECT user_id, password_hash FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows >= 1) {
    $stmt->bind_result($userId, $storedPasswordHash);
    $stmt->fetch();

    // Verify password
    if (password_verify($password, $storedPasswordHash)) {
        echo json_encode([
            "success" => true,
            "message" => "Login successful.",
            "user_id" => $userId
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Incorrect password."
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "User not found."
    ]);
}

// Close connections
$stmt->close();
$conn->close();
?>
