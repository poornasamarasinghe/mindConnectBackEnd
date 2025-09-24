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

// Get doctor ID and date from GET parameters
$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';

if ($doctor_id === 0 || empty($date)) {
    echo json_encode(["status" => "error", "message" => "Invalid doctor ID or date"]);
    exit();
}

// Fetch availability record for this doctor and date
$sql = "SELECT place, times FROM doctoravailability WHERE doctor_id = ? AND date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $doctor_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$slots = [];
while ($row = $result->fetch_assoc()) {
    $times = json_decode($row['times'], true); // decode JSON array
    foreach ($times as $time) {
        $slots[] = [
            "time" => $time,
            "place" => $row['place']
        ];
    }
}

echo json_encode(["status" => "success", "data" => $slots]);

$stmt->close();
$conn->close();
?>
