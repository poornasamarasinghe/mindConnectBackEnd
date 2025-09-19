<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: *");

require_once '../database/dbconnection.php';

try {
    $conn = (new DbConnection())->getConnection();

    // Fetch all posts randomly
    $sql = "
        SELECT 
            p.post_id,
            p.user_id,
            p.title,
            p.content,
            p.category,
            p.post_file_url,
            p.created_at,
            u.user_type,
            COALESCE(d.display_name, c.display_name, u.username) AS actual_name,
            u.profile_pic_url AS actual_profile_pic
        FROM post p
        JOIN user u ON p.user_id = u.user_id
        LEFT JOIN doctor d ON u.user_id = d.user_id
        LEFT JOIN counsellor c ON u.user_id = c.user_id
        ORDER BY RAND()
    ";

    $result = $conn->query($sql);

    $posts = [];
    $default_avatar = "https://mindconnectstorage.blob.core.windows.net/profilepicture/default-avatar-profile-icon-social-600nw-1677509740.webp";

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // If regular user, anonymize
            if ($row['user_type'] === 'normal') {
                $author_name = "Anonymous User";
                $author_profile_pic = $default_avatar;
            } else {
                $author_name = $row['actual_name'];
                $author_profile_pic = $row['actual_profile_pic'] ?? $default_avatar;
            }

            $posts[] = [
                "post_id" => (int)$row['post_id'],
                "user_id" => (int)$row['user_id'],
                "title" => $row['title'],
                "content" => $row['content'],
                "category" => $row['category'],
                "post_file_url" => $row['post_file_url'],
                "created_at" => $row['created_at'],
                "author_name" => $author_name,
                "author_profile_pic" => $author_profile_pic,
            ];
        }
    }

    echo json_encode([
        "success" => true,
        "posts" => $posts
    ]);

    $conn->close();
} catch (Exception $err) {
    echo json_encode([
        "success" => false,
        "message" => $err->getMessage()
    ]);
}
?>
