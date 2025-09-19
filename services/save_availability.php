<?php
header("Content-Type: application/json");

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0); // handle preflight

error_reporting(E_ERROR | E_PARSE);

include __DIR__ . "/../database/dbconnection.php";

$db = new DbConnection();
$conn = $db->getConnection();

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Database connection not found"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$doctor_id = $data['doctor_id'] ?? null;
$date = $data['date'] ?? null;
$slots = $data['slots'] ?? [];

if (!$doctor_id || !$date || empty($slots)) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

// ✅ Check if doctor exists (only by user_id)
$doctor_check = $conn->prepare("SELECT user_id FROM User WHERE user_id=?");
$doctor_check->bind_param("i", $doctor_id);
$doctor_check->execute();
$doctor_res = $doctor_check->get_result();

if ($doctor_res->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Doctor ID does not exist"]);
    exit;
}

// Delete old availability
$conn->query("DELETE FROM DoctorAvailability WHERE doctor_id='$doctor_id' AND date='$date'");

// Insert new availability
$stmt = $conn->prepare("INSERT INTO DoctorAvailability (doctor_id, date, place, times) VALUES (?, ?, ?, ?)");

foreach ($slots as $slot) {
    $place = $slot['place'];
    $times = json_encode($slot['times']);
    $stmt->bind_param("isss", $doctor_id, $date, $place, $times);

    try {
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        echo json_encode(["status" => "error", "message" => "Failed to save availability: " . $e->getMessage()]);
        exit;
    }
}

echo json_encode(["status" => "success", "message" => "Availability saved successfully"]);
?>
