<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

$user_id = intval($_SESSION['user_id']);
$title   = trim($_POST['title'] ?? '');
$course  = trim($_POST['course'] ?? '');
$content = $_POST['content'] ?? '';

if ($title === '' || $course === '' || $content === '') {
    echo json_encode(["status" => "error", "message" => "All fields required"]);
    exit();
}

$stmt = $conn->prepare("INSERT INTO notes (user_id, title, course, content) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $user_id, $title, $course, $content);

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error"]);
}
?>
