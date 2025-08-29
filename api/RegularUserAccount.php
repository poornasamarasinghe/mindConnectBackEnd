<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

include '../database/dbconnection.php'; // update with your actual DB connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    $user_id = intval($input['user_id']);

    $conn = (new DbConnection())->getConnection();
    $stmt = $conn->prepare("SELECT username, user_type FROM User WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($name, $role);

    if ($stmt->fetch()) {
        echo json_encode([
            "status" => "success",
            "name" => $name,
            "role" => $role
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "User not found"
        ]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request"
    ]);
}
?>
