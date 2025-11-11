<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id = intval($_SESSION['user_id']);
$query   = $_GET['q'] ?? '';

if (!$query) {
    echo json_encode([]);
    exit();
}

// Fetch matching personal tasks
$tasks_stmt = $conn->prepare("SELECT id, title FROM tasks WHERE user_id = ? AND title LIKE CONCAT('%', ?, '%') LIMIT 5");
$tasks_stmt->bind_param("is", $user_id, $query);
$tasks_stmt->execute();
$tasks_res = $tasks_stmt->get_result();

// Fetch matching group tasks
$group_stmt = $conn->prepare("SELECT gt.id, gt.title 
    FROM group_tasks gt
    JOIN group_members gm ON gt.group_id = gm.group_id
    WHERE gm.user_id = ? AND gt.title LIKE CONCAT('%', ?, '%')
    LIMIT 5");
$group_stmt->bind_param("is", $user_id, $query);
$group_stmt->execute();
$group_res = $group_stmt->get_result();

$results = [];

while ($row = $tasks_res->fetch_assoc()) {
    $results[] = ['id' => $row['id'], 'title' => $row['title'], 'type' => 'Task'];
}

while ($row = $group_res->fetch_assoc()) {
    $results[] = ['id' => $row['id'], 'title' => $row['title'], 'type' => 'Group Task'];
}

echo json_encode($results);
?>