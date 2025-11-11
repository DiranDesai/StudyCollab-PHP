<?php
session_start();
require '../includes/db.php';

$user_id = $_SESSION['user_id'] ?? 0;
header('Content-Type: application/json');

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$action = $_POST['action'] ?? '';

switch($action) {

    // ---------- ADD TASK ----------
    case 'add_task':
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $due   = $_POST['due_date'] ?? '';

        if (!$title || !$due) {
            echo json_encode(['success' => false, 'error' => 'Please fill required fields.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO tasks (user_id,title,description,due_date,status,created_at,updated_at) VALUES (?,?,?,?, 'Pending', NOW(), NOW())");
        $stmt->bind_param("isss", $user_id, $title, $desc, $due);

        if ($stmt->execute()) {
            $id = $stmt->insert_id;
            echo json_encode([
                'success' => true,
                'task' => [
                    'id' => $id,
                    'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
                    'description' => htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'),
                    'due_date' => $due,
                    'status' => 'Pending'
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'DB error while adding task.']);
        }
        $stmt->close();
        break;

    // ---------- TOGGLE COMPLETE / REVERT ----------
    case 'toggle_complete':
        $id = intval($_POST['id'] ?? 0);
        $target = $_POST['target'] === 'Completed' ? 'Completed' : 'Pending';

        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid task ID.']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE tasks SET status=?, updated_at=NOW() WHERE id=? AND user_id=?");
        $stmt->bind_param('sii', $target, $id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $task = $conn->query("SELECT * FROM tasks WHERE id=$id AND user_id=$user_id")->fetch_assoc();
            echo json_encode(['success' => true, 'task' => $task]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Unable to update task status.']);
        }
        $stmt->close();
        break;

    // ---------- DELETE TASK ----------
    case 'delete_task':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) { 
            echo json_encode(['success' => false, 'error' => 'Invalid ID']); 
            exit; 
        }

        $stmt = $conn->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'id' => $id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Unable to delete task (not found).']);
        }
        $stmt->close();
        break;

    // ---------- EDIT TASK ----------
    case 'edit_task':
        $id    = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $due   = $_POST['due_date'] ?? '';

        if ($id <= 0 || !$title || !$due) {
            echo json_encode(['success' => false, 'error' => 'Missing/invalid fields']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE tasks SET title=?, description=?, due_date=?, updated_at=NOW() WHERE id=? AND user_id=?");
        $stmt->bind_param("sssii", $title, $desc, $due, $id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            $task = $conn->query("SELECT * FROM tasks WHERE id=$id AND user_id=$user_id")->fetch_assoc();
            echo json_encode([
                'success' => true,
                'task' => [
                    'id' => $task['id'],
                    'title' => htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8'),
                    'description' => htmlspecialchars($task['description'], ENT_QUOTES, 'UTF-8'),
                    'due_date' => $task['due_date'],
                    'status' => $task['status']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Unable to update task.']);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        break;
}