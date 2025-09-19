<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

if (!isset($_GET['doctor_id']) || !isset($_GET['date'])) {
    echo json_encode(["status" => "error", "message" => "Missing required parameters"]);
    exit;
}

$doctor_id = intval($_GET['doctor_id']);
$date = $_GET['date'];

// Include DB connection
require_once '../database/dbconnection.php';
$db = new DbConnection();
$conn = $db->getConnection();

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Database connection not found"]);
    exit;
}

$sql = "SELECT place, times FROM DoctorAvailability WHERE doctor_id=? AND date=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $doctor_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$slots = [];
while ($row = $result->fetch_assoc()) {
    $times = json_decode($row['times'], true);
    foreach ($times as $time) {
        $slots[] = [
            "time" => $time,
            "place" => $row['place']
        ];
    }
}

echo json_encode(["status" => "success", "data" => $slots]);
