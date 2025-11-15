<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$user_name = $_SESSION['name'] ?? 'Student';
$user_email = $_SESSION['email'] ?? 'student@example.com';
$first_name = explode(' ', trim($user_name))[0];
$profile_photo = $_SESSION['profile_photo'] ?? null;

// Fetch groups the user belongs to
$stmt = $conn->prepare("
    SELECT g.id AS group_id, g.group_name, g.leader_id
    FROM group_members gm
    JOIN groups g ON gm.group_id = g.id
    WHERE gm.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userGroups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch tasks for user's groups
$group_ids = array_column($userGroups, 'group_id');
$group_ids_placeholder = implode(',', $group_ids ?: [0]);

$tasks_sql = "
    SELECT gt.*, g.group_name, g.leader_id, u.fullname AS leader_name
    FROM group_tasks gt
    JOIN groups g ON gt.group_id = g.id
    LEFT JOIN users u ON g.leader_id = u.id
    WHERE gt.group_id IN ($group_ids_placeholder)
    ORDER BY gt.due_date ASC
";
$groupTasks = $conn->query($tasks_sql);

// Separate pending and completed tasks
$pendingTasks = [];
$completedTasks = [];
if ($groupTasks && $groupTasks->num_rows > 0) {
    while ($row = $groupTasks->fetch_assoc()) {
        if (strtolower($row['status']) === 'pending') $pendingTasks[] = $row;
        else $completedTasks[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Group Tasks | StudyCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<style>
:root { --primary:#1a73e8; --surface:#fff;  --bg:#f5f5f7; --text:#202124; --muted:#5f6368; }
body { font-family:'Google Sans',sans-serif; margin:0; background:var(--bg); color:var(--text); overflow-x:hidden; }
main { margin-left:250px; padding:90px 30px 40px; transition: margin-left 0.3s; }
main.collapsed { margin-left:80px; }
.task-section-title { font-size:1rem; font-weight:600; color:var(--primary); margin-bottom:15px; }
.task-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:16px; margin-top:20px; }
.task-card { background:var(--surface); border:1px solid #e0e0e0; border-radius:12px; padding:16px; display:flex; flex-direction:column; justify-content:space-between; transition:transform .2s, box-shadow .2s; }
.task-card:hover { transform:translateY(-2px); box-shadow:0 2px 8px rgba(0,0,0,0.08); }
.task-title { font-weight:600; font-size:1rem; margin-bottom:5px; }
.task-desc { color:#555; margin-bottom:10px; }
.task-meta { font-size:0.85rem; color:#888; margin-bottom:10px; }
.task-actions { display:flex; gap:8px; flex-wrap:wrap; }
.task-actions .btn { flex:1; font-size:0.85rem; border-radius:8px; transform: scale(0.9); wrap: nowrap;) }
.btn-add { background:var(--primary); color:#fff; }
.btn-complete { background:#198754; color:#fff; }
.btn-edit { background:#ffc107; color:#fff; }
.btn-delete { background:#dc3545; color:#fff; }
.text-decoration-line-through { text-decoration:line-through; color:#555; }
.modal-content { border-radius:12px; }


/* Card */
.card-apple {
    background:var(--card);
    padding:22px;
    border-radius:var(--radius);
    border:1px solid #e5e5e7;
    box-shadow:0 4px 20px rgba(0,0,0,0.04);
    transition:all 0.25s ease;
}

/* Optional: smooth input focus effect */
.task-card .form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Make placeholder bold */
.form-control::placeholder {
    font-weight: 600;
    color: #6c757d; /* slightly muted */
}

@media(max-width:768px){ main{margin-left:0 !important; padding-top:80px;} .task-cards{grid-template-columns:1fr;} }
</style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>
<main id="main">
    <?php include '../includes/navbar.php'; ?>

    <div id="alertBox"></div>

    <!-- Add Task Form -->
    <div class="card shadow-sm p-4 mt-5 task-card" style="max-width: 500px; margin: 2rem auto; border-radius: 12px;">
        <h5 class="fw-semibold mb-3">Add Group Task</h5>
        <form id="addTaskForm">

            <!-- Task Title -->
            <div class="mb-3 position-relative">
                <i class="bi bi-pencil position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" name="title" class="form-control ps-5 fw-bold" placeholder="Task Title" required>
            </div>

            <!-- Description -->
            <div class="mb-3 position-relative">
                <i class="bi bi-card-text position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <textarea name="description" class="form-control ps-5 fw-bold" rows="2" placeholder="Description (optional)"></textarea>
            </div>

            <!-- Due Date -->
            <div class="mb-3 position-relative">
                <i class="bi bi-calendar-date position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="date" name="due_date" class="form-control ps-5 fw-bold" required>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center fw-bold" style="gap: 0.5rem;">
                <i class="bi bi-plus-circle"></i> Add Task
            </button>
        </form>
    </div>
    <!-- Pending Tasks -->
    <div class="task-section-title mt-4">Pending Group Tasks</div>
    <div class="task-cards" id="pendingTasksContainer">
        <?php if ($pendingTasks): foreach ($pendingTasks as $row): ?>
        <div class="card-apple rounded-4" id="task-<?= $row['id'] ?>">
            <div class="task-title"><?= htmlspecialchars($row['title']) ?> 
                <small class="text-muted">(<?= htmlspecialchars($row['group_name']) ?>)</small>
            </div>
            <div class="task-desc"><?= htmlspecialchars($row['description']) ?></div>
            <div class="task-meta">Due: <?= $row['due_date'] ?> | 
                <span class="text-warning fw-semibold"><?= ucfirst($row['status']) ?></span> | 
                Leader: <?= htmlspecialchars($row['leader_name'] ?? 'Unknown') ?>
            </div>
            <div class="task-actions">
                <button class="btn btn-success btn-sm complete-btn" data-id="<?= $row['id'] ?>"><i class="bi bi-check-lg"></i> Complete</button>
                <?php if ((int)$row['leader_id'] === (int)$user_id): ?>
                    <button class="btn btn-secondary btn-sm edit-btn" data-id="<?= $row['id'] ?>" data-title="<?= htmlspecialchars($row['title']) ?>" data-desc="<?= htmlspecialchars($row['description']) ?>" data-date="<?= $row['due_date'] ?>"><i class="bi bi-pencil"></i> Edit</button>
                    <button class="btn btn-danger btn-sm delete-btn" data-id="<?= $row['id'] ?>"><i class="bi bi-trash"></i> Delete</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; else: ?>
            <p class="text-muted">No pending group tasks.</p>
        <?php endif; ?>
    </div>

    <!-- Completed Tasks -->
    <div class="task-section-title mt-4">Completed Group Tasks</div>
    <div class="task-cards" id="completedTasksContainer">
        <?php if ($completedTasks): foreach ($completedTasks as $row): ?>
        <div class="task-card bg-light" id="task-<?= $row['id'] ?>">
            <div class="task-title text-success"><?= htmlspecialchars($row['title']) ?> 
                <small class="text-muted">(<?= htmlspecialchars($row['group_name']) ?>)</small>
            </div>
            <div class="task-desc"><?= htmlspecialchars($row['description']) ?></div>
            <div class="task-meta small text-secondary">Completed | Leader: <?= htmlspecialchars($row['leader_name'] ?? 'Unknown') ?></div>
            <div class="task-actions">
                <button class="btn btn-warning btn-sm revert-btn" data-id="<?= $row['id'] ?>"><i class="bi bi-arrow-counterclockwise"></i> Revert</button>
                <?php if ((int)$row['leader_id'] === (int)$user_id): ?>
                    <button class="btn btn-danger btn-sm delete-btn" data-id="<?= $row['id'] ?>"><i class="bi bi-trash"></i> Delete</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; else: ?>
            <p class="text-muted">No completed tasks.</p>
        <?php endif; ?>
    </div>
</main>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
  <div class="modal-dialog">
    <form id="editTaskForm" class="modal-content p-3">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Edit Task</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="task_id" id="edit_task_id">
        <label>Title</label>
        <input type="text" class="form-control mb-2" name="title" id="edit_title" required>
        <label>Description</label>
        <textarea class="form-control mb-2" name="description" id="edit_description" required></textarea>
        <label>Due Date</label>
        <input type="date" class="form-control" name="due_date" id="edit_due_date" required>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
function showAlert(msg, type="success") {
    $("#alertBox").html(`<div class="alert alert-${type} alert-dismissible fade show" role="alert">${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`);
    setTimeout(() => $(".alert").alert('close'), 3000);
}

$(function(){
    // Add task
    $("#addTaskForm").submit(function(e){
        e.preventDefault();
        $.post("group_task_actions.php", $(this).serialize()+"&action=add", res=>{
            showAlert(res.message, res.success?'success':'danger');
            if(res.success) setTimeout(()=>location.reload(), 1000);
        }, "json");
    });

    // Complete task
    $(document).on("click", ".complete-btn", function(){
        $.post("group_task_actions.php", {id: $(this).data("id"), action:"complete"}, res=>{
            if(res.success){ showAlert(res.message); $("#task-"+$(this).data("id")).fadeOut(); }
        }, "json");
    });

    // Revert task
    $(document).on("click", ".revert-btn", function(){
        $.post("group_task_actions.php", {id: $(this).data("id"), action:"revert"}, res=>{
            if(res.success){ showAlert(res.message); $("#task-"+$(this).data("id")).fadeOut(); }
        }, "json");
    });

    // Edit modal
    $(document).on("click", ".edit-btn", function(){
        $("#edit_task_id").val($(this).data("id"));
        $("#edit_title").val($(this).data("title"));
        $("#edit_description").val($(this).data("desc"));
        $("#edit_due_date").val($(this).data("date"));
        new bootstrap.Modal("#editTaskModal").show();
    });

    $("#editTaskForm").submit(function(e){
        e.preventDefault();
        $.post("group_task_actions.php", $(this).serialize()+"&action=edit", res=>{
            showAlert(res.message, res.success?'success':'danger');
            if(res.success) setTimeout(()=>location.reload(), 1000);
        }, "json");
    });

    // Delete
    $(document).on("click", ".delete-btn", function(){
        if(!confirm("Delete this task?")) return;
        const id = $(this).data("id");
        $.post("group_task_actions.php", {id, action:"delete"}, res=>{
            if(res.success){ $("#task-"+id).fadeOut(); showAlert("Task deleted"); }
        }, "json");
    });
});
</script>
</body>
</html>
