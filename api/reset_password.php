<?php

header("Access-Control-Allow-Origin: *"); // or restrict to your frontend origin
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once __DIR__ . '/../services/PasswordResetService.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'];
$password = $data['password'];

if (!$email || !$password) {
    echo json_encode(["success" => false, "message" => "Email and password are required."]);
    exit;
}
$service = new PasswordResetService();
$result = $service->resetPassword($email, $password);

if ($result['success']) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to reset password."]);
}
?>