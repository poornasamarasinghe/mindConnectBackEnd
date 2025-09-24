<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // or restrict to your frontend origin
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
require_once __DIR__ . '/../database/dbconnection.php';

// Create DB connection
$db = new DbConnection();
$conn = $db->getConnection();

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit();
}

// Get POSTed data
$data = json_decode(file_get_contents("php://input"), true);

$doctor_id = isset($data['doctor_id']) ? intval($data['doctor_id']) : 0;
$date = isset($data['date']) ? $data['date'] : '';
$slot_time = isset($data['slot']) ? $data['slot'] : '';

if ($doctor_id === 0 || empty($date) || empty($slot_time)) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit();
}

// Fetch the availability record
$sql = "SELECT availability_id, times FROM doctoravailability WHERE doctor_id = ? AND date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $doctor_id, $date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "No availability found"]);
    exit();
}

$row = $result->fetch_assoc();
$times = json_decode($row['times'], true);

// Check if the slot exists
if (!in_array($slot_time, $times)) {
    echo json_encode(["status" => "error", "message" => "Slot already booked or does not exist"]);
    exit();
}

// Remove booked slot from times array
$times = array_values(array_diff($times, [$slot_time]));

// Update database
$sql_update = "UPDATE doctoravailability SET times = ? WHERE availability_id = ?";
$stmt_update = $conn->prepare($sql_update);
$times_json = json_encode($times);
$stmt_update->bind_param("si", $times_json, $row['availability_id']);

if ($stmt_update->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to book slot"]);
}

$stmt->close();
$stmt_update->close();
$conn->close();
?>
