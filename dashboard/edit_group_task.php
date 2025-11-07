<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = intval($_SESSION['user_id']);

if(!isset($_GET['id'])){
    header('Location: group_tasks.php');
    exit();
}

$task_id = intval($_GET['id']);

// Fetch the task
$stmt = $conn->prepare("SELECT * FROM group_tasks WHERE id=? AND leader_id=? LIMIT 1");
$stmt->bind_param("ii", $task_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();
$stmt->close();

if(!$task){
    echo "Task not found or you don't have permission.";
    exit();
}

// Handle Update
if(isset($_POST['update_task'])){
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("UPDATE group_tasks SET title=?, description=?, due_date=?, updated_at=NOW() WHERE id=? AND leader_id=?");
    $stmt->bind_param("sssii", $title, $description, $due_date, $task_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header('Location: group_tasks.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Group Task</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Edit Group Task</h3>
    <form method="POST">
        <div class="mb-3">
            <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($task['title']); ?>" required>
        </div>
        <div class="mb-3">
            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($task['description']); ?></textarea>
        </div>
        <div class="mb-3">
            <input type="date" class="form-control" name="due_date" value="<?php echo $task['due_date']; ?>" required>
        </div>
        <button type="submit" name="update_task" class="btn btn-primary">Update Task</button>
        <a href="group_tasks.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>