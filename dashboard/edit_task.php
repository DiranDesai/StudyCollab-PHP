<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle update submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = intval($_POST['task_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = trim($_POST['due_date']);

    if ($task_id && $title && $due_date) {
        $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, due_date = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sssii", $title, $description, $due_date, $task_id, $user_id);
        if ($stmt->execute()) {
            header("Location: tasks.php?update=success");
            exit();
        } else {
            $error = "Failed to update task.";
        }
        $stmt->close();
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Handle loading task for editing
if (isset($_GET['id'])) {
    $task_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();
    $stmt->close();

    if (!$task) {
        header("Location: tasks.php?error=notfound");
        exit();
    }
} else {
    header("Location: tasks.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Task | StudentCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
    body {
        overflow-x: hidden;
    }

    /* Sidebar */
    .sidebar {
        height: 100vh;
        background: #ff4500;
        color: #fff;
        position: fixed;
        width: 250px;
        padding-top: 20px;
    }

    .sidebar a {
        color: #fff;
        display: block;
        padding: 10px 20px;
        text-decoration: none;
    }

    .sidebar a:hover, .sidebar a.active {
        background-color: rgba(255,255,255,0.15);
    }

    /* Main content */
    .main-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f8f9fa;
    }

    .card {
        max-width: 600px;
        margin: auto;
        box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        border-radius: 12px;
    }

    .btn-primary {
        background-color: #ff4500;
        border: none;
    }

    .btn-primary:hover {
        background-color: #e03e00;
    }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="text-center mb-4">
        <h4><i class="bi bi-people-fill"></i> StudCollab</h4>
    </div>
    <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
    <a href="tasks.php" class="active"><i class="bi bi-list-task me-2"></i>My Tasks</a>
    <a href="group_tasks.php"><i class="bi bi-people me-2"></i>Group Work</a>
    <a href="calendar.php"><i class="bi bi-calendar-event me-2"></i>Calendar</a>
    <a href="resources.php"><i class="bi bi-journal-bookmark me-2"></i>Resources</a>
</div>

<!-- Main content -->
<div class="main-content">
    <nav class="navbar navbar-light bg-light px-3 mb-4">
        <span class="navbar-brand mb-0 h5">
            Edit Task
        </span>
    </nav>

    <div class="card p-4">
        <h5 class="mb-3 text-center text-danger"><i class="bi bi-pencil-square"></i> Edit Task Details</h5>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="edit_task.php">
            <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($task['id']); ?>">

            <div class="mb-3">
                <label for="title" class="form-label">Task Title</label>
                <input type="text" name="title" id="title" class="form-control" value="<?php echo htmlspecialchars($task['title']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="4"><?php echo htmlspecialchars($task['description']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="due_date" class="form-label">Due Date</label>
                <input type="date" name="due_date" id="due_date" class="form-control" value="<?php echo htmlspecialchars($task['due_date']); ?>" required>
            </div>

            <div class="d-flex justify-content-between">
                <a href="tasks.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Task</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>