<?php

header("Access-Control-Allow-Origin: *"); // or restrict to your frontend origin
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");


session_start();
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'];

if (!$email) {
    echo json_encode(["success" => false, "message" => "Email is required."]);
    exit;
}

$otp = rand(100000, 999999);
$_SESSION['otp'] = $otp;
$_SESSION['otp_email'] = $email;

$subject = "MindConnect - Your OTP Code";
$message = "Hi,\n\nYour OTP code for resetting your password is: $otp\n\nIf you didn’t request this, please ignore this email.\n\n- MindConnect Team";
$headers = "From: no-reply@mindconnect.com";

if (mail($email, $subject, $message, $headers)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to send OTP."]);
}
?>