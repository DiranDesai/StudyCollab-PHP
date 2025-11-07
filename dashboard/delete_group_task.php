<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = intval($_SESSION['user_id']);

if(isset($_GET['id'])){
    $task_id = intval($_GET['id']);

    // Delete only if the user is leader/admin
    $stmt = $conn->prepare("DELETE FROM group_tasks WHERE id=? AND leader_id=?");
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

header('Location: group_tasks.php');
exit();