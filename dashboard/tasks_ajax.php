<?php
require '../includes/db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? 0;

// Complete task
if (isset($_POST['complete_task'])) {
    $id = intval($_POST['complete_task']);
    $conn->query("UPDATE tasks SET status='Completed', updated_at=NOW() WHERE id=$id AND user_id=$user_id");
    echo 'success';
    exit;
}

// Delete task
if (isset($_POST['delete_task'])) {
    $id = intval($_POST['delete_task']);
    $conn->query("DELETE FROM tasks WHERE id=$id AND user_id=$user_id");
    echo 'success';
    exit;
}

// Get task details
if (isset($_GET['get_task'])) {
    $id = intval($_GET['get_task']);
    $task = $conn->query("SELECT * FROM tasks WHERE id=$id AND user_id=$user_id")->fetch_assoc();
    echo json_encode($task);
    exit;
}

// Update task
if (isset($_POST['task_id'])) {
    $id = intval($_POST['task_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("UPDATE tasks SET title=?, description=?, due_date=?, updated_at=NOW() WHERE id=? AND user_id=?");
    $stmt->bind_param("sssii", $title, $description, $due_date, $id, $user_id);
    $stmt->execute();
    echo 'success';
    exit;
}
?>