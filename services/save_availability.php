<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

require_once '../database/dbconnection.php';

try {
    $conn = (new DbConnection())->getConnection();
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['doctor_id'], $data['date'], $data['slots'])) {
        throw new Exception("Missing required fields");
    }

    $user_id = intval($data['doctor_id']); // this is actually user_id
    $date = $data['date'];
    $slots = $data['slots'];

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

    // delete old slots for the same date (to overwrite)
    $del = $conn->prepare("DELETE FROM doctoravailability WHERE doctor_id = ? AND date = ?");
    $del->bind_param("is", $doctor_id, $date);
    $del->execute();
    $del->close();

    // insert new slots
    $ins = $conn->prepare("INSERT INTO doctoravailability (doctor_id, date, place, times) VALUES (?, ?, ?, ?)");
    foreach ($slots as $slot) {
        $place = $slot['place'];
        $times = json_encode($slot['times']);
        $ins->bind_param("isss", $doctor_id, $date, $place, $times);
        $ins->execute();
    }
    $ins->close();

    echo json_encode(["status" => "success", "message" => "Availability updated"]);

    $conn->close();

} catch (Exception $err) {
    echo json_encode(["status" => "error", "message" => $err->getMessage()]);
}
?>
