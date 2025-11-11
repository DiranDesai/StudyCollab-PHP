<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit();
}

$user_id = intval($_SESSION['user_id']);
$action  = $_POST['action'] ?? '';

if ($action === 'add_event') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date    = $_POST['due_date'] ?? '';
    $event_type  = $_POST['event_type'] ?? 'task';
    $group_id    = $_POST['group_id'] ?? null;

    // Validation
    if (!$title || !$due_date) {
        echo json_encode(['success'=>false,'message'=>'Title and Date are required']);
        exit();
    }

    if ($event_type === 'group-task' && (!$group_id || !is_numeric($group_id))) {
        echo json_encode(['success'=>false,'message'=>'Group is required for group tasks']);
        exit();
    }

    // Insert event
    $stmt = $conn->prepare("INSERT INTO calendar_events (user_id, group_id, title, description, event_type, due_date, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $group_id_val = $event_type === 'group-task' ? intval($group_id) : null;
    $stmt->bind_param("iissss", $user_id, $group_id_val, $title, $description, $event_type, $due_date);
    if ($stmt->execute()) {
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false,'message'=>$stmt->error]);
    }
    $stmt->close();
    exit();
}

// Add more actions here if needed
echo json_encode(['success'=>false,'message'=>'Invalid action']);