<?php

header("Access-Control-Allow-Origin: *"); // or restrict to your frontend origin
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
require_once __DIR__ . '/../services/PasswordResetService.php';

session_start();
header("Content-Type: application/json");


$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'];

if (!$email) {
    echo json_encode(["success" => false, "message" => "Email is required."]);
    exit;
}
    $service = new PasswordResetService();
    $result = $service->createOtp(trim($email));

if ($result['success']) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" =>$result['message']]);
}
?>