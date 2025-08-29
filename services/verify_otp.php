<?php

header("Access-Control-Allow-Origin: *"); // or restrict to your frontend origin
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");


session_start();
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'];
$otp = $data['otp'];

if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_email'])) {
    echo json_encode(["success" => false, "message" => "OTP not sent or session expired."]);
    exit;
}

if ($_SESSION['otp_email'] === $email && $_SESSION['otp'] == $otp) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid OTP."]);
}
?>