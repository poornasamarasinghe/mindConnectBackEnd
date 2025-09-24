<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

require_once '../database/dbconnection.php';

try {
    $conn = (new DbConnection())->getConnection();
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';

    if ($query === '') {
        echo json_encode(["success" => false, "message" => "Search query is required"]);
        exit;
    }

    $searchTerm = "%" . $conn->real_escape_string($query) . "%";

    function searchProfessionals($conn, $table, $idField, $roleName, $searchTerm) {
        $sql = "
            SELECT p.$idField AS professional_id, u.user_id, u.username, u.user_type, u.profile_pic_url, 
                   p.display_name, ? AS role
            FROM $table p
            INNER JOIN user u ON p.user_id = u.user_id
            WHERE (p.display_name LIKE ? OR u.username LIKE ?) AND u.account_status = 'verified'
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $roleName, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();

        $professionals = [];
        while ($row = $result->fetch_assoc()) {
            $professionals[] = [
                "id" => intval($row['professional_id']),
                "user_id" => intval($row['user_id']),
                "display_name" => $row['display_name'],
                "username" => $row['username'],
                "role" => $row['role'],
                "profile_pic_url" => $row['profile_pic_url'] ?: null
            ];
        }
        $stmt->close();
        return $professionals;
    }

    $doctors = searchProfessionals($conn, "doctor", "doctor_id", "doctor", $searchTerm);
    $counsellors = searchProfessionals($conn, "counsellor", "counsellor_id", "counsellor", $searchTerm);

    $results = array_merge($doctors, $counsellors);

    echo json_encode(["success" => true, "results" => $results]);
    $conn->close();

} catch (Exception $err) {
    echo json_encode(["success" => false, "message" => $err->getMessage()]);
}
