<?php
// update_profile.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: *");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

header("Content-Type: application/json; charset=utf-8");
ini_set('display_errors', 0);

require_once __DIR__ . '/../database/dbconnection.php';
require_once __DIR__ . '/../Utilities/AzureHelper.php'; // adjust path if different

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(["success" => false, "message" => "Only POST allowed"]);
        exit;
    }

    $conn = (new DbConnection())->getConnection();
    if (!$conn) {
        echo json_encode(["success" => false, "message" => "DB connection failed"]);
        exit;
    }
    $conn->set_charset("utf8mb4");

    // sanitize inputs
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $user_type = isset($_POST['user_type']) ? trim($_POST['user_type']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $display_name = isset($_POST['display_name']) ? trim($_POST['display_name']) : null;

    if ($user_id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid user_id"]);
        exit;
    }
    if ($username === '') {
        echo json_encode(["success" => false, "message" => "Username required"]);
        exit;
    }

    // Begin: optional profile pic upload
    $profile_pic_url = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_pic'];
        // basic validation
        $allowed = ['jpg','jpeg','png','webp','gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            echo json_encode(["success" => false, "message" => "Invalid image type. Allowed: jpg,jpeg,png,webp,gif"]);
            exit;
        }

        // create unique filename
        $filename = "user_{$user_id}_" . uniqid() . "." . $ext;

        $azure = new AzureHelper();
        $containerName = "profilepicture";
        $uploadResult = $azure->uploadFile($containerName, $file["tmp_name"], $filename);

        if (!$uploadResult['success']) {
            echo json_encode(["success" => false, "message" => "File upload failed: " . $uploadResult['message']]);
            exit;
        }

        $profile_pic_url = $uploadResult['url'];
    }

    // Update user table: update username and optionally profile_pic_url
    if ($profile_pic_url !== null) {
        $stmt = $conn->prepare("UPDATE `user` SET username = ?, profile_pic_url = ? WHERE user_id = ?");
        if (!$stmt) throw new Exception("Prepare failed (update user)");
        $stmt->bind_param("ssi", $username, $profile_pic_url, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE `user` SET username = ? WHERE user_id = ?");
        if (!$stmt) throw new Exception("Prepare failed (update user username)");
        $stmt->bind_param("si", $username, $user_id);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Failed to update user");
    }
    $stmt->close();

    // If doctor or counsellor, upsert display_name in corresponding table
    if ($user_type === 'doctor' || $user_type === 'counsellor') {
        // choose table
        $tbl = ($user_type === 'doctor') ? 'doctor' : 'counsellor';

        // check if row exists
        $check = $conn->prepare("SELECT {$tbl}_id FROM {$tbl} WHERE user_id = ?");
        if (!$check) throw new Exception("Prepare failed (check {$tbl})");
        $check->bind_param("i", $user_id);
        $check->execute();
        $res = $check->get_result();
        $exists = ($res && $res->num_rows > 0);
        $check->close();

        if ($display_name !== null && $display_name !== "") {
            if ($exists) {
                $up = $conn->prepare("UPDATE {$tbl} SET display_name = ? WHERE user_id = ?");
                if (!$up) throw new Exception("Prepare failed (update {$tbl})");
                $up->bind_param("si", $display_name, $user_id);
                if (!$up->execute()) {
                    $up->close();
                    throw new Exception("Failed to update display_name in {$tbl}");
                }
                $up->close();
            } else {
                // insert new row with display_name (set other columns to NULL/default)
                $ins = $conn->prepare("INSERT INTO {$tbl} (user_id, verification_document, verification_remarks, display_name) VALUES (?, NULL, NULL, ?)");
                if (!$ins) throw new Exception("Prepare failed (insert {$tbl})");
                $ins->bind_param("is", $user_id, $display_name);
                if (!$ins->execute()) {
                    $ins->close();
                    throw new Exception("Failed to insert display_name into {$tbl}");
                }
                $ins->close();
            }
        }
    }

    // return updated values
    // fetch current user row to return profile url etc
    $stmt2 = $conn->prepare("SELECT username, profile_pic_url FROM `user` WHERE user_id = ?");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $userRow = $res2->fetch_assoc();
    $stmt2->close();

    $resp = [
        "success" => true,
        "message" => "Profile updated",
        "username" => $userRow['username'] ?? $username,
        "profile_pic_url" => $userRow['profile_pic_url'] ?? $profile_pic_url,
    ];

    // include display_name if doctor/counsellor
    if ($user_type === 'doctor' || $user_type === 'counsellor') {
        $resp['display_name'] = $display_name ?: null;
    }

    echo json_encode($resp);
    $conn->close();
} catch (Exception $ex) {
    http_response_code(500);
    error_log("update_profile error: " . $ex->getMessage());
    echo json_encode(["success" => false, "message" => $ex->getMessage()]);
    exit;
}
