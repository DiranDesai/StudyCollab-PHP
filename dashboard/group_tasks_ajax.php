<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo 'error';
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Complete Task
if(isset($_POST['complete_task'])){
    $task_id = intval($_POST['complete_task']);

    echo $task_id;

    // Only allow the leader or group member to complete the task
    $stmt = $conn->prepare("
        UPDATE group_tasks 
        SET status = 'Completed' 
        WHERE id = ? AND (leader_id = ? OR group_id IN (
            SELECT group_id FROM group_members WHERE user_id = ?
        ))
    ");
    $stmt->bind_param("iii", $task_id, $user_id, $user_id);

    if($stmt->execute()){
        echo 'success';
    } else {
        echo 'error';
    }
    $stmt->close();
    exit;
}

// Delete Task
if(isset($_POST['delete_task'])){
    $task_id = intval($_POST['delete_task']);

    $stmt = $conn->prepare("
        DELETE FROM group_tasks 
        WHERE id = ? AND (leader_id = ? OR group_id IN (
            SELECT group_id FROM group_members WHERE user_id = ?
        ))
    ");
    $stmt->bind_param("iii", $task_id, $user_id, $user_id);

    if($stmt->execute()){
        echo 'success';
    } else {
        echo 'error';
    }
    $stmt->close();
    exit;
}

// Edit Task
if(isset($_POST['edit_task'])){
    $task_id = intval($_POST['edit_task']);
    $title = $_POST['title'];
    $description = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("
        UPDATE group_tasks 
        SET title = ?, description = ?, due_date = ?
        WHERE id = ? AND (leader_id = ? OR group_id IN (
            SELECT group_id FROM group_members WHERE user_id = ?
        ))
    ");
    $stmt->bind_param("sssiii", $title, $description, $due_date, $task_id, $user_id, $user_id);

    if($stmt->execute()){
        echo 'success';
    } else {
        echo 'error';
    }
    $stmt->close();
    exit;
}

echo 'error';
?>
