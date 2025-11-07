<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require '../includes/db.php';

$user_id = intval($_SESSION['user_id']);
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Student';
$user_role = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Student';

// Handle Add Group Task
if (isset($_POST['add_group_task'])) {
    $group_name = trim($_POST['group_name']);
    $leader_id = $user_id;
    $group_id = !empty($_POST['group_id']) ? intval($_POST['group_id']) : NULL; // optional
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];

    $stmt = $conn->prepare("
        INSERT INTO group_tasks 
        (group_name, leader_id, group_id, title, description, due_date, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())
    ");

    if(!$stmt){
        die("Prepare failed: " . $conn->error);
    }

    // Always bind 6 variables, NULL works for optional group_id
    $stmt->bind_param("siisss", $group_name, $leader_id, $group_id, $title, $description, $due_date);

    $stmt->execute();
    $stmt->close();

    header('Location: group_tasks.php');
    exit();
}

// Fetch all groups the user belongs to
$groups_res = $conn->query("
    SELECT g.id, g.group_name 
    FROM groups g
    INNER JOIN group_members gm ON g.id = gm.group_id
    WHERE gm.user_id = $user_id
");

// Fetch all group tasks (including tasks with no group)
$tasks_res = $conn->query("
    SELECT gt.*, g.group_name AS existing_group_name
    FROM group_tasks gt
    LEFT JOIN groups g ON gt.group_id = g.id
    WHERE gt.leader_id = $user_id OR gt.group_id IN (
        SELECT group_id FROM group_members WHERE user_id = $user_id
    )
    ORDER BY gt.due_date ASC
");

if(!$tasks_res){
    die("Query failed: " . $conn->error);
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Group Tasks | StudentCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
/* ---- Keep your existing CSS ---- */
body { overflow-x: hidden; }
.sidebar { height: 100vh; background: #ff4500; color: #fff; position: fixed; width: 250px; display: flex; flex-direction: column; justify-content: space-between; padding-top: 20px; padding-bottom: 20px; }
.sidebar a { color: #fff; display: block; padding: 10px 20px; text-decoration: none; }
.sidebar a:hover, .sidebar a.active { background-color: rgba(255,255,255,0.15); }
.profile-section { border-top: 1px solid rgba(255,255,255,0.2); padding: 10px 20px 0 20px; }
.profile-btn { color: #fff; background: none; border: none; width: 100%; text-align: left; padding: 10px 0; }
.profile-btn:hover { background-color: rgba(255,255,255,0.1); border-radius: 6px; }
.dropdown-menu { background-color: #ff6700; color: #fff; border: none; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
.dropdown-item:hover { background-color: #f8f9fa; }
.main-content { margin-left: 250px; padding: 40px 20px 20px 20px; min-height: 100vh; background: #f8f9fa; }
.dropdown-item.text-danger { color: #fff !important; background-color: transparent !important; }
.dropdown-item.text-danger:hover { background-color: rgba(255,255,255,0.15) !important; color: #fff !important; }

/* Add Task Form */
.add-task-card { max-width: 600px; margin: 0 auto 30px auto; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); background: #fff; }
.add-task-card .card-header { color: #ff4500; font-weight: bold; text-align: center; }
.add-task-card button[type="submit"] { background-color: #ff4500; border-color: #ff4500; color: #fff; }
.add-task-card button[type="submit"]:hover { background-color: #e03e00; border-color: #e03e00; }

/* Task Cards */
.task-card { border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 15px; background: #fff; position: relative; transition: transform 0.2s; }
.task-card:hover { transform: translateY(-3px); }
.task-title { font-weight: 600; font-size: 1.1rem; margin-bottom: 5px; }
.task-desc { font-size: 0.95rem; color: #555; margin-bottom: 10px; }
.task-meta { font-size: 0.85rem; color: #888; margin-bottom: 10px; }
.task-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.task-actions .btn { flex: 1; }

/* Status styles */
.status-pending { color: #ffc107; font-weight: 600; }
.status-completed { color: #198754; font-weight: 600; }
.task-card.bg-light { opacity: 0.85; }
.text-decoration-line-through { text-decoration: line-through; }
.task-section-title { margin-top: 30px; margin-bottom: 15px; font-weight: 600; font-size: 1.25rem; color: #ff4500; }

/* Edit popup */
.edit-task-popup { position: absolute; top: -10px; left: 0; right: 0; z-index: 10; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
</style>
</head>
<body>

<div class="sidebar">
    <div>
        <div class="text-center mb-4"><h4><i class="bi bi-people-fill"></i> StudCollabo</h4></div>
        <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        <a href="tasks.php"><i class="bi bi-list-task me-2"></i>My Tasks</a>
        <a href="group_tasks.php" class="active"><i class="bi bi-people me-2"></i>Group Work</a>
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
    <!-- Add Group Task Form -->
    <div class="card add-task-card">
        <div class="card-header text-center text-primary fw-bold">Add New Group Task</div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <input type="text" class="form-control form-control-lg" id="group_name_input" name="group_name" placeholder="Group Name" required>
                </div>
                
                <div class="mb-3">
                    <select name="group_id" class="form-select" id="group_select">
                        <option value="">Select Group (optional)</option>
                        <?php while($g = $groups_res->fetch_assoc()): ?>
                            <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['group_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <input type="text" class="form-control form-control-lg" name="title" placeholder="Task Title" required>
                </div>
                <div class="mb-3">
                    <textarea class="form-control" name="description" rows="2" placeholder="Description (optional)"></textarea>
                </div>
                <div class="mb-3">
                    <input type="date" class="form-control" name="due_date" required>
                </div>
                <button type="submit" name="add_group_task" class="btn btn-primary w-100"><i class="bi bi-plus-circle"></i> Add Task</button>
            </form>
        </div>
    </div>

    <!-- Group Tasks -->
    <div class="task-section-title">Group Tasks</div>
    <div class="row" id="group-tasks-container">
        <?php if($tasks_res && $tasks_res->num_rows > 0): ?>
            <?php while($row = $tasks_res->fetch_assoc()): ?>
            <div class="col-md-6 task-card-wrapper" id="task-wrapper-<?php echo $row['id']; ?>">
                <div class="task-card" id="task-<?php echo $row['id']; ?>">
                    <div class="task-title"><?php echo htmlspecialchars($row['title']); ?> (<?php echo htmlspecialchars($row['existing_group_name'] ?? 'Ungrouped'); ?>)</div>
                    <div class="task-desc"><?php echo htmlspecialchars($row['description']); ?></div>
                    <div class="task-meta">
                        Due: <?php echo $row['due_date']; ?> | <span class="status-pending">Pending</span>
                    </div>
                    <div class="task-actions">
                        <button class="btn btn-success btn-sm complete-btn" data-id="<?php echo $row['id']; ?>"><i class="bi bi-check-lg"></i> Complete</button>
                        <button class="btn btn-warning btn-sm edit-btn" data-id="<?php echo $row['id']; ?>"><i class="bi bi-pencil"></i> Edit</button>
                        <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $row['id']; ?>"><i class="bi bi-trash"></i> Delete</button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center mt-2"><p class="text-muted">No group tasks!</p></div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// JS: keep your existing code (edit, delete, complete)
const groupSelect = document.getElementById('group_select');
const groupInput = document.getElementById('group_name_input');
groupSelect.addEventListener('change', () => {
    const selectedOption = groupSelect.selectedOptions[0];
    groupInput.value = selectedOption && selectedOption.dataset.name ? selectedOption.dataset.name : '';
});

document.querySelectorAll('.complete-btn').forEach(button => {
    button.addEventListener('click', () => {
        const taskId = button.dataset.id;
        fetch('group_tasks_ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `complete_task=${taskId}`
        }).then(res => res.text()).then(res => {
            if(res === 'success') location.reload();
            else alert('Error marking task as completed.');
        });
    });
});

document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', () => {
        if(confirm('Are you sure you want to delete this task?')){
            const taskId = button.dataset.id;
            fetch('group_tasks_ajax.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `delete_task=${taskId}`
            }).then(res => res.text()).then(res => {
                if(res === 'success') document.getElementById('task-wrapper-'+taskId).remove();
                else alert('Error deleting task.');
            });
        }
    });
});

document.querySelectorAll('.edit-btn').forEach(button => {
    button.addEventListener('click', () => {
        const taskCard = button.closest('.task-card');
        const taskId = button.dataset.id;
        if(taskCard.querySelector('.edit-task-popup')) return;

        const title = taskCard.querySelector('.task-title').innerText.split(' (')[0];
        const desc = taskCard.querySelector('.task-desc').innerText;
        const dueDateText = taskCard.querySelector('.task-meta').innerText;
        const due_date = dueDateText.match(/\d{4}-\d{2}-\d{2}/)[0];

        const popup = document.createElement('div');
        popup.classList.add('edit-task-popup');
        popup.innerHTML = `
            <form class="edit-form">
                <div class="mb-2"><input type="text" class="form-control form-control-sm" name="title" value="${title}" required></div>
                <div class="mb-2"><textarea class="form-control form-control-sm" name="description" rows="2">${desc}</textarea></div>
                <div class="mb-2"><input type="date" class="form-control form-control-sm" name="due_date" value="${due_date}" required></div>
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-50">Save</button>
                    <button type="button" class="btn btn-secondary btn-sm w-50 cancel-btn">Cancel</button>
                </div>
            </form>
        `;
        taskCard.prepend(popup);

        popup.querySelector('.cancel-btn').addEventListener('click', () => popup.remove());
        popup.querySelector('.edit-form').addEventListener('submit', e => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('edit_task', taskId);
            fetch('group_tasks_ajax.php', { method: 'POST', body: formData })
                .then(res => res.text())
                .then(res => { if(res==='success') location.reload(); else alert('Error updating task.'); });
        });
    });
});
</script>
</body>
</html>