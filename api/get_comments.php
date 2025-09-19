<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

require_once '../database/dbconnection.php';

try {
    $conn = (new DbConnection())->getConnection();

    if (!isset($_GET['post_id'])) {
        throw new Exception("Post ID is required");
    }

    $post_id = intval($_GET['post_id']);

    $sql = "
        SELECT 
            c.comment_id,
            c.content,
            c.created_at,
            u.user_type,
            COALESCE(d.display_name, co.display_name, u.username) AS author_name,
            u.profile_pic_url AS profile_pic
        FROM comment c
        JOIN user u ON c.user_id = u.user_id
        LEFT JOIN doctor d ON u.user_id = d.user_id
        LEFT JOIN counsellor co ON u.user_id = co.user_id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $comments = [];
    $default_avatar = "https://mindconnectstorage.blob.core.windows.net/profilepicture/default-avatar-profile-icon-social-600nw-1677509740.webp";

    while ($row = $result->fetch_assoc()) {
        if ($row['user_type'] === 'normal') {
            $author_name = "Anonymous User";
            $profile_pic = $default_avatar;
        } else {
            $author_name = $row['author_name'];
            $profile_pic = $row['profile_pic'] ?? $default_avatar;
        }

        $comments[] = [
            "comment_id" => (int)$row['comment_id'],
            "content" => $row['content'],
            "created_at" => $row['created_at'],
            "author_name" => $author_name,
            "profile_pic" => $profile_pic,
        ];
    }

    echo json_encode([
        "success" => true,
        "comments" => $comments
    ]);

    $stmt->close();
    $conn->close();

} catch (Exception $err) {
    echo json_encode([
        "success" => false,
        "message" => $err->getMessage()
    ]);
}
