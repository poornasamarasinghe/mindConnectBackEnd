<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require_once '../database/dbconnection.php';

// Receive user_id from POST
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
if ($user_id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid user ID."]);
    exit();
}

// Fetch user info
$userQuery = $conn->prepare("SELECT name, profile_pic FROM user WHERE user_id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$userResult = $userQuery->get_result();
$userInfo = $userResult->fetch_assoc();
$userQuery->close();

if (!$userInfo) {
    echo json_encode(["success" => false, "message" => "User not found."]);
    exit();
}

// Fetch user's own posts
$postQuery = $conn->prepare("SELECT post_id, content, created_at FROM post WHERE user_id = ? ORDER BY created_at DESC");
$postQuery->bind_param("i", $user_id);
$postQuery->execute();
$postResult = $postQuery->get_result();

$posts = [];
while ($row = $postResult->fetch_assoc()) {
    $posts[] = $row;
}
$postQuery->close();

// ✅ Fetch latest posts from other users
$otherPostsQuery = $conn->prepare("
    SELECT 
        p.post_id,
        p.user_id,
        u.name AS author_name,
        p.title,
        p.content,
        p.category,
        p.created_at
    FROM post p
    JOIN user u ON p.user_id = u.user_id
    WHERE p.user_id != ?
    ORDER BY p.created_at DESC
    LIMIT 50
");
$otherPostsQuery->bind_param("i", $user_id);
$otherPostsQuery->execute();
$otherPostsResult = $otherPostsQuery->get_result();

$randomPosts = [];
while ($row = $otherPostsResult->fetch_assoc()) {
    $randomPosts[] = $row;
}
$otherPostsQuery->close();

// Optional shuffle for randomness
shuffle($randomPosts);

$conn->close();

// Return combined data
echo json_encode([
    "success" => true,
    "user" => $userInfo,
    "own_posts" => $posts,
    "random_posts" => $randomPosts
]);
?>
