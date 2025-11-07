<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require '../includes/db.php';

$user_id = intval($_SESSION['user_id']);
$user_name = $_SESSION['name'] ?? 'Student';
$user_role = ucfirst($_SESSION['role'] ?? 'Student');

// Handle Add Task
if (isset($_POST['add_task'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, description, due_date, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'Pending', NOW(), NOW())");
    $stmt->bind_param("isss", $user_id, $title, $description, $due_date);
    $stmt->execute();
    $stmt->close();

    header('Location: tasks.php');
    exit();
}

// Fetch tasks
$pendingTasks = $conn->query("SELECT * FROM tasks WHERE user_id = $user_id AND status='Pending' ORDER BY due_date ASC");
$completedTasks = $conn->query("SELECT * FROM tasks WHERE user_id = $user_id AND status='Completed' ORDER BY due_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Tasks | StudentCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { overflow-x: hidden; }
.sidebar {
    height: 100vh;
    background: #ff4500;
    color: #fff;
    position: fixed;
    width: 250px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding-top: 20px;
    padding-bottom: 20px;
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
.profile-section {
    border-top: 1px solid rgba(255,255,255,0.2);
    padding: 10px 20px 0 20px;
}
.profile-btn {
    color: #fff;
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    padding: 10px 0;
}
.profile-btn:hover {
    background-color: rgba(255,255,255,0.1);
    border-radius: 6px;
}
.dropdown-menu {
    background-color: #ff6700;
    color: #fff;
    border: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
.dropdown-item:hover { background-color: rgba(255,255,255,0.15); }
.main-content {
    margin-left: 250px;
    padding: 40px 20px 20px 20px;
    min-height: 100vh;
    background: #f8f9fa;
}
.dropdown-item.text-danger {
    color: #fff !important;
    background-color: transparent !important;
}
.dropdown-item.text-danger:hover {
    background-color: rgba(255,255,255,0.15) !important;
    color: #fff !important;
}
.add-task-card {
    max-width: 600px;
    margin: 0 auto 30px auto;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    background: #fff;
}
.add-task-card .card-header { color: #ff4500; font-weight: bold; text-align: center; }
.add-task-card button[type="submit"] {
    background-color: #ff4500;
    border-color: #ff4500;
    color: #fff;
}
.add-task-card button[type="submit"]:hover {
    background-color: #e03e00;
    border-color: #e03e00;
}
.task-card {
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px;
    margin-bottom: 15px;
    background: #fff;
    transition: transform 0.2s;
}
.task-card:hover { transform: translateY(-3px); }
.task-title { font-weight: 600; font-size: 1.1rem; margin-bottom: 5px; }
.task-desc { font-size: 0.95rem; color: #555; margin-bottom: 10px; }
.task-meta { font-size: 0.85rem; color: #888; margin-bottom: 10px; }
.task-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.task-actions .btn { flex: 1; }
.status-pending { color: #ffc107; font-weight: 600; }
.status-completed { color: #198754; font-weight: 600; }
.task-card.bg-light { opacity: 0.85; }
.text-decoration-line-through { text-decoration: line-through; }
.task-section-title { margin-top: 30px; margin-bottom: 15px; font-weight: 600; font-size: 1.25rem; color: #ff4500; }
</style>
</head>
<body>

<div class="sidebar">
    <div>
        <div class="text-center mb-4">
            <h4><i class="bi bi-people-fill"></i> StudCollabo</h4>
        </div>
        <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        <a href="tasks.php" class="active"><i class="bi bi-list-task me-2"></i>My Tasks</a>
        <a href="group_tasks.php"><i class="bi bi-people me-2"></i>Group Work</a>
        <a href="calendar.php"><i class="bi bi-calendar-event me-2"></i>Calendar</a>
        <a href="resources.php"><i class="bi bi-journal-bookmark me-2"></i>Resources</a>
    </div>

    <div class="profile-section dropdown">
        <button class="profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-2"></i>
            <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_role); ?>)
        </button>
        <ul class="dropdown-menu w-100">
            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear"></i> Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>
</div>

<div class="main-content">

    <!-- Add Task Form -->
    <div class="card add-task-card">
        <div class="card-header text-center text-primary fw-bold">Add New Task</div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <input type="text" class="form-control form-control-lg" name="title" placeholder="Task Title" required>
                </div>
                <div class="mb-3">
                    <textarea class="form-control" name="description" rows="2" placeholder="Description (optional)"></textarea>
                </div>
                <div class="mb-3">
                    <input type="date" class="form-control" name="due_date" required>
                </div>
                <button type="submit" name="add_task" class="btn btn-primary w-100"><i class="bi bi-plus-circle"></i> Add Task</button>
            </form>
        </div>
    </div>

    <!-- Pending Tasks -->
    <div class="task-section-title">Pending Tasks</div>
    <div class="row" id="pending-tasks-container">
        <?php if($pendingTasks && $pendingTasks->num_rows > 0): ?>
            <?php while($row = $pendingTasks->fetch_assoc()): ?>
            <div class="col-md-6 task-card-wrapper" id="task-wrapper-<?php echo $row['id']; ?>">
                <div class="task-card" id="task-<?php echo $row['id']; ?>">
                    <div class="task-title"><?php echo htmlspecialchars($row['title']); ?></div>
                    <div class="task-desc"><?php echo htmlspecialchars($row['description']); ?></div>
                    <div class="task-meta">Due: <?php echo $row['due_date']; ?> | <span class="status-pending">Pending</span></div>
                    <div class="task-actions">
                        <button class="btn btn-success btn-sm complete-btn" data-id="<?php echo $row['id']; ?>"><i class="bi bi-check-lg"></i> Complete</button>
                        <button class="btn btn-warning btn-sm edit-btn" data-id="<?php echo $row['id']; ?>"><i class="bi bi-pencil"></i> Edit</button>
                        <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $row['id']; ?>"><i class="bi bi-trash"></i> Delete</button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center mt-2"><p class="text-muted">No pending tasks!</p></div>
        <?php endif; ?>
    </div>

    <!-- Completed Tasks -->
    <div class="task-section-title">Completed Tasks</div>
    <div class="row" id="completed-tasks-container">
        <?php if($completedTasks && $completedTasks->num_rows > 0): ?>
            <?php while($row = $completedTasks->fetch_assoc()): ?>
            <div class="col-md-6 task-card-wrapper" id="task-wrapper-<?php echo $row['id']; ?>">
                <div class="task-card bg-light" id="task-<?php echo $row['id']; ?>">
                    <div class="task-title text-decoration-line-through"><?php echo htmlspecialchars($row['title']); ?></div>
                    <div class="task-desc"><?php echo htmlspecialchars($row['description']); ?></div>
                    <div class="task-meta">Due: <?php echo $row['due_date']; ?> | <span class="status-completed">Completed</span></div>
                    <div class="task-actions">
                        <button class="btn btn-warning btn-sm edit-btn" data-id="<?php echo $row['id']; ?>"><i class="bi bi-pencil"></i> Edit</button>
                        <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $row['id']; ?>"><i class="bi bi-trash"></i> Delete</button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center mt-2"><p class="text-muted">No completed tasks!</p></div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning text-white">
        <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Task</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editTaskForm">
          <input type="hidden" name="task_id" id="editTaskId">
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" class="form-control" name="title" id="editTitle" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" id="editDescription" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Due Date</label>
            <input type="date" class="form-control" name="due_date" id="editDueDate" required>
          </div>
          <button type="submit" class="btn btn-warning w-100"><i class="bi bi-save"></i> Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Complete task
document.querySelectorAll('.complete-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    fetch('tasks_ajax.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'complete_task=' + btn.dataset.id
    }).then(r => r.text()).then(r => { if (r==='success') location.reload(); });
  });
});

// Delete task
document.querySelectorAll('.delete-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    if (confirm('Delete this task?')) {
      fetch('tasks_ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'delete_task=' + btn.dataset.id
      }).then(r => r.text()).then(r => {
        if (r==='success') document.getElementById('task-wrapper-'+btn.dataset.id).remove();
      });
    }
  });
});

// Edit task
document.querySelectorAll('.edit-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const id = btn.dataset.id;
    fetch('tasks_ajax.php?get_task=' + id)
      .then(r => r.json())
      .then(task => {
        document.getElementById('editTaskId').value = task.id;
        document.getElementById('editTitle').value = task.title;
        document.getElementById('editDescription').value = task.description;
        document.getElementById('editDueDate').value = task.due_date;
        new bootstrap.Modal(document.getElementById('editTaskModal')).show();
      });
  });
});

// Save edit
document.getElementById('editTaskForm').addEventListener('submit', e => {
  e.preventDefault();
  const data = new URLSearchParams(new FormData(e.target));
  fetch('tasks_ajax.php', {method:'POST', body:data})
    .then(r => r.text())
    .then(r => {
      if (r==='success') location.reload();
      else alert('Error updating task.');
    });
});
</script>
</body>
</html>