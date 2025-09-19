<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: *");
require_once __DIR__ . '/../Utilities/AzureHelper.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    return;
}

require_once '../database/dbconnection.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn = (new DbConnection())->getConnection();

        $username = $_POST['username'] ?? '';
        $display_name = $_POST['displayname'] ?? ''; // new field
        $email = $_POST['email'] ?? '';
        $user_type = $_POST['usertype'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmpassword = $_POST['confirmpassword'] ?? '';
        $verificationDocumentUrl = null;

        // Basic validation
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

        // If doctor/counsellor, display name is required
        if (in_array($user_type, ['doctor', 'counsellor']) && empty($display_name)) {
            echo json_encode(["success" => false, "message" => "Display name is required for professionals."]);
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
        $checkEmail->close();

        // Password hashing
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Handle document upload if doctor or counsellor
        if (in_array($user_type, ['doctor', 'counsellor'])) {
            if (!isset($_FILES['verification_document'])) {
                echo json_encode(["success" => false, "message" => "Verification document required."]);
                return;
            }

            $file = $_FILES['verification_document'];
            $filename = $email . "." . basename($file["name"]);

            // Validate file extension
            if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== "pdf") {
                echo json_encode(["success" => false, "message" => "Only PDF documents allowed."]);
                return;
            }

            // Upload to Azure Blob Storage
            $azure = new AzureHelper();
            $containerName = "verifictiondocuments"; // ensure correct name
            $uploadResult = $azure->uploadFile($containerName, $file["tmp_name"], $filename);

            if (!$uploadResult['success']) {
                echo json_encode(["success" => false, "message" => "Document upload failed: " . $uploadResult['message']]);
                return;
            }

            $verificationDocumentUrl = $uploadResult['url'];
        }

        // Account status logic
        $account_status = ($user_type === "normal") ? "verified" : "pending";

        // Insert into user table
        $stmt = $conn->prepare("INSERT INTO user (username, email, password_hash, user_type, account_status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $username, $email, $password_hash, $user_type, $account_status);

        if (!$stmt->execute()) {
            echo json_encode(["success" => false, "message" => "Failed to register user."]);
            return;
        }

        $userId = $stmt->insert_id;
        $stmt->close();

        // Insert into doctor/counsellor tables with display_name
        if ($user_type === "doctor" && !empty($verificationDocumentUrl)) {
            $stmt = $conn->prepare("INSERT INTO doctor (user_id, verification_document, verification_remarks, display_name) VALUES (?, ?, NULL, ?)");
            $stmt->bind_param("iss", $userId, $verificationDocumentUrl, $display_name);
            $stmt->execute();
            $stmt->close();
        }

        if ($user_type === "counsellor" && !empty($verificationDocumentUrl)) {
            $stmt = $conn->prepare("INSERT INTO counsellor (user_id, verification_document, verification_remarks, display_name) VALUES (?, ?, NULL, ?)");
            $stmt->bind_param("iss", $userId, $verificationDocumentUrl, $display_name);
            $stmt->execute();
            $stmt->close();
        }

        $conn->close();

        echo json_encode(["success" => true, "message" => "Registration successful."]);
    }
} catch (Exception $err) {
    echo json_encode(["success" => false, "message" => $err->getMessage()]);
}
