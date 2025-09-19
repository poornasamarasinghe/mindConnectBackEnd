<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
error_reporting(E_ERROR | E_PARSE);

include __DIR__ . "/../database/dbconnection.php";

$db = new DbConnection();
$conn = $db->getConnection();

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Database connection not found"]);
    exit;
}

$doctor_id = $_GET['doctor_id'] ?? null;
$date = $_GET['date'] ?? null;

if (!$doctor_id || !$date) {
    echo json_encode(["status" => "error", "message" => "doctor_id and date required"]);
    exit;
}

$stmt = $conn->prepare("SELECT availability_id, place, times FROM DoctorAvailability WHERE doctor_id=? AND date=?");
$stmt->bind_param("is", $doctor_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$availabilities = [];
while ($row = $result->fetch_assoc()) {
    $row['times'] = json_decode($row['times'], true);
    $availabilities[] = $row;
}

echo json_encode(["status" => "success", "data" => $availabilities]);
?>
