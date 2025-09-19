<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['doctor_id'], $data['date'], $data['slot'])) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

$doctor_id = intval($data['doctor_id']);
$date = $data['date'];
$time = $data['slot']['time'];
$place = $data['slot']['place'];

// Include DB connection
require_once '../database/dbconnection.php';
$db = new DbConnection();
$conn = $db->getConnection();

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Database connection not found"]);
    exit;
}

// Save to Appointment table
$sql = "INSERT INTO Appointment (doctor_id, date, time, place) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isss", $doctor_id, $date, $time, $place);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Appointment booked successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to book appointment"]);
}
