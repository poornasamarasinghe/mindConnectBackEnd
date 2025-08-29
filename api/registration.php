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

        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $user_type = $_POST['usertype'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmpassword = $_POST['confirmpassword'] ?? '';

        if (empty($username) || empty($email) || empty($user_type) || empty($password) || empty($confirmpassword)) {
            echo json_encode(["success" => false, "message" => "All fields are required."]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["success" => false, "message" => "Invalid email format."]);
            return;
        }

        if ($password !== $confirmpassword) {
            echo json_encode(["success" => false, "message" => "Passwords do not match."]);
            return;
        }

        // Check for existing email
        $checkEmail = $conn->prepare("SELECT user_id FROM user WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $result = $checkEmail->get_result();
        if ($result->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Email already exists."]);
            return;
        }

        // Password hashing
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Handle document upload if doctor or counsellor
        if (in_array($user_type, ['doctor', 'counsellor'])) {
            if (!isset($_FILES['verification_document'])) {
                echo json_encode(["success" => false, "message" => "Verification document required."]);
                return;
            }

            $file = $_FILES['verification_document'];
            $targetDir = $_SERVER['DOCUMENT_ROOT'] . "/mindConnect/uploads/";
            if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);

            $filename = time() . "_" . basename($file["name"]);
            $targetFile = $targetDir . $filename;

            if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== "pdf") {
                echo json_encode(["success" => false, "message" => "Only PDF documents allowed."]);
                return;
            }

            if (!move_uploaded_file($file["tmp_name"], $targetFile)) {
                echo json_encode(["success" => false, "message" => "Document upload failed."]);
                return;
            }
        }

        // Account status logic
        $account_status = ($user_type === "normal") ? "verified" : "pending";

        // Insert into DB
        $stmt = $conn->prepare("INSERT INTO user (username, email, password_hash, user_type, account_status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $username, $email, $password_hash, $user_type, $account_status);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Registration successful."]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to register."]);
        }

        $conn->close();
    }
} catch (Exception $err) {
    echo json_encode($err->getMessage());
}
