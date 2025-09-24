<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

require_once '../database/dbconnection.php';

try {
    $conn = (new DbConnection())->getConnection();

    if (!isset($_GET['doctor_id'], $_GET['date'])) {
        throw new Exception("Missing doctor_id or date");
    }

    $user_id = intval($_GET['doctor_id']); // user_id
    $date = $_GET['date'];

    // 🔎 Map user_id → doctor_id
    $stmt = $conn->prepare("SELECT doctor_id FROM doctor WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new Exception("Doctor not found for user_id {$user_id}");
    }
    $doctor_id = $row['doctor_id'];

    // fetch availability
    $sql = "SELECT availability_id, place, times FROM doctoravailability WHERE doctor_id = ? AND date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $doctor_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($r = $result->fetch_assoc()) {
        $data[] = [
            "availability_id" => (int)$r['availability_id'],
            "place" => $r['place'],
            "times" => json_decode($r['times'], true) ?? []
        ];
    }

    echo json_encode(["status" => "success", "data" => $data]);

    $stmt->close();
    $conn->close();

} catch (Exception $err) {
    echo json_encode(["status" => "error", "message" => $err->getMessage()]);
}
?>
