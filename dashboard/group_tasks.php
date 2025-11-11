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
if($groupTasks && $groupTasks->num_rows > 0){
    while($row = $groupTasks->fetch_assoc()){
        if(strtolower($row['status']) === 'pending') $pendingTasks[] = $row;
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
<style>
:root { --primary:#1a73e8; --surface:#fff; --bg:#f8f9fa; --text:#202124; --muted:#5f6368; }
body { font-family:'Google Sans',sans-serif; margin:0; background:var(--bg); color:var(--text); overflow-x:hidden; }
.sidebar { width:250px; height:100vh; background:var(--surface); border-right:1px solid #ddd; position:fixed; top:0; left:0; transition: width 0.3s; overflow:hidden; }
.sidebar.collapsed { width:80px; }
.sidebar .logo { font-size:1.3rem; font-weight:600; color:var(--primary); padding:20px; display:flex; align-items:center; gap:8px; }
.sidebar ul { list-style:none; padding:0; margin:0; }
.sidebar ul li a { display:flex; align-items:center; gap:15px; padding:12px 20px; color:#333; text-decoration:none; font-weight:500; border-radius:10px; margin:5px 10px; transition:0.3s; }
.sidebar ul li a:hover, .sidebar ul li a.active { background:#e8f0fe; color:var(--primary); }
.sidebar.collapsed ul li a span { display:none; }
.topbar { display:flex; justify-content:space-between; align-items:center; gap:8px; height:64px; background: var(--surface); padding: 0 20px; border-bottom:1px solid #ddd; position: fixed; top:0; left:0; width:100%; z-index:99; }
.top-left { display:flex; align-items:center; gap:10px; }
.top-left img { height:32px; }
.search-box { position:relative; width:320px; }
.search-box input { width:100%; padding:8px 35px; border-radius:25px; border:1px solid #ddd; background:#f1f3f4; }
.search-box i { position:absolute; top:8px; left:12px; color:#888; }
.profile-btn { border:none; background:transparent; display:flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:50%; font-weight:600; font-size:14px; color:#fff; background:var(--primary); cursor:pointer; position:relative; }
.profile-btn img { width:100%; height:100%; border-radius:50%; object-fit:cover; }
.profile-menu { position:absolute; right:0; top:50px; width:260px; background:#fff; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.1); display:none; z-index:100; }
.profile-menu.active { display:block; }
.profile-header { padding:16px; text-align:center; border-bottom:1px solid #eee; }
.profile-header img { width:60px; height:60px; border-radius:50%; margin-bottom:10px; object-fit:cover; }
.profile-header h6 { margin:0 0 4px; font-weight:600; }
.profile-header small { color:#555; }
.profile-menu a { display:block; padding:10px 16px; text-decoration:none; color:#333; }
.profile-menu a:hover { background:#f5f5f5; }
main { margin-left:250px; padding:90px 30px 40px; transition: margin-left 0.3s; }
main.collapsed { margin-left:80px; }
.task-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px; margin-top: 20px; }
.task-card { background: var(--surface); border: 1px solid #e0e0e0; border-radius: 12px; padding: 16px; display:flex; flex-direction:column; justify-content:space-between; transition: transform 0.2s, box-shadow 0.2s; }
.task-card:hover { transform: translateY(-2px); box-shadow:0 2px 8px rgba(0,0,0,0.08); }
.task-title { font-weight:600; font-size:1.1rem; margin-bottom:5px; }
.task-desc { color:#555; margin-bottom:10px; }
.task-meta { font-size:0.85rem; color:#888; margin-bottom:10px; }
.task-actions { display:flex; gap:8px; flex-wrap:wrap; }
.task-actions .btn { flex:1; font-size:0.85rem; border-radius:8px; }
.btn-add { background: var(--primary); color:#fff; }
.btn-complete { background:#198754; color:#fff; }
.btn-edit { background:#ffc107; color:#fff; }
.btn-delete { background:#dc3545; color:#fff; }
.text-decoration-line-through { text-decoration: line-through; color:#555; }
.task-section-title { font-size:1.25rem; font-weight:600; color:var(--primary); margin-bottom:15px; }
.modal-content { border-radius:12px; }
@media(max-width:768px){ main{margin-left:0 !important; padding-top:80px;} .sidebar{display:none;} .topbar{padding-left:20px !important;} .task-cards{grid-template-columns:1fr;} }
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="logo"><i class="bi bi-journal-text"></i> <span>StudyCollabo</span></div>
    <ul>
        <li><a href="dashboard.php"><i class="bi bi-grid"></i> <span>Dashboard</span></a></li>
        <li><a href="tasks.php"><i class="bi bi-person-check"></i> <span>My Tasks</span></a></li>
        <li><a href="group_tasks.php" class="active"><i class="bi bi-people"></i> <span>Group Tasks</span></a></li>
        <li><a href="calendar.php"><i class="bi bi-calendar3"></i> <span>Calendar</span></a></li>
        <li><a href="discussions.php"><i class="bi bi-chat-dots"></i> <span>Discussions</span></a></li>
    </ul>
</div>

<main id="main">
    <div class="topbar" id="topbar">
        <div class="top-left">
            <button class="btn btn-light" id="toggleSidebar"><i class="bi bi-list"></i></button>
            <img src="assets/img/SClogo.png" alt="StudyCollabo Logo">
        </div>
        <div class="search-box mx-auto">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search group tasks..." id="searchBox">
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="profile-btn" id="profileBtn">
                <?php if($profile_photo): ?>
                    <img src="<?= htmlspecialchars($profile_photo) ?>" alt="Avatar">
                <?php else: ?>
                    <?= strtoupper(substr($first_name,0,1)) ?>
                <?php endif; ?>
            </button>
            <div class="profile-menu" id="profileMenu">
                <div class="profile-header">
                    <?php if($profile_photo): ?>
                        <img src="<?= htmlspecialchars($profile_photo) ?>" alt="Avatar">
                    <?php else: ?>
                        <div style="width:60px;height:60px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:600;margin:0 auto 10px;">
                            <?= strtoupper(substr($first_name,0,1)) ?>
                        </div>
                    <?php endif; ?>
                    <h6>Hey, <?= htmlspecialchars($first_name) ?></h6>
                    <small><?= htmlspecialchars($user_email) ?></small>
                </div>
                <a href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a>
                <a href="../auth/logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
            </div>
        </div>
    </div>

    <!-- Add Task Form -->
     <div class="task-section-title mt-3">Create New Group & Task</div>
     <div class="task-card p-3 mb-4 shadow-sm">
        <form id="addTaskForm">
            <div class="row g-2">
                <!-- Optional: New Group Name -->
                 <div class="col-md-3">
                    <input type="text" name="new_group_name" class="form-control" placeholder="New Group Name">
                </div>

                <!-- Or Select Existing Group -->
                <div class="col-md-3">
                    <select name="group_id" class="form-select">
                        <option value="">Select Existing Group</option>
                        <?php foreach($userGroups as $g): ?>
                            <option value="<?= $g['group_id'] ?>"><?= htmlspecialchars($g['group_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Task Title -->
                 <div class="col-md-2">
                    <input type="text" name="title" class="form-control" placeholder="Task Title" required>
                </div>
                
                <!-- Task Description -->
                <div class="col-md-2">
                    <input type="text" name="description" class="form-control" placeholder="Description">
                </div>
                
                <!-- Due Date -->
                <div class="col-md-2">
                    <input type="date" name="due_date" class="form-control" required>
                </div>
                
                <!-- Submit -->
                 <div class="col-md-12 mt-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Create Group & Add Task</button>
                </div>
            </div>
        </form>
    </div>


    <!-- Pending Tasks -->
    <div class="task-section-title mt-4">Pending Group Tasks</div>
    <div class="task-cards" id="pendingTasksContainer">
        <?php if($pendingTasks): foreach($pendingTasks as $row): ?>
        <div class="task-card" id="task-wrapper-<?= $row['id'] ?>">
            <div class="task-title"><?= htmlspecialchars($row['title']) ?> <small class="text-muted">(<?= htmlspecialchars($row['group_name']) ?>)</small></div>
            <div class="task-desc"><?= htmlspecialchars($row['description']) ?></div>
            <div class="task-meta">Due: <?= $row['due_date'] ?> | <span class="text-warning fw-semibold"><?= ucfirst($row['status']) ?></span> | Leader: <?= htmlspecialchars($row['leader_name'] ?? 'Unknown') ?></div>
            <div class="task-actions mt-2">
                <button class="btn btn-success btn-sm complete-btn" data-id="<?= $row['id'] ?>"><i class="bi bi-check-lg"></i> Complete</button>
                <?php if((int)$row['leader_id'] === (int)$user_id): ?>
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
        <?php if($completedTasks): foreach($completedTasks as $row): ?>
        <div class="task-card bg-light" id="task-wrapper-<?= $row['id'] ?>">
            <div class="task-title text-success"><?= htmlspecialchars($row['title']) ?> <small class="text-muted">(<?= htmlspecialchars($row['group_name']) ?>)</small></div>
            <div class="task-desc"><?= htmlspecialchars($row['description']) ?></div>
            <div class="task-meta small text-secondary">Completed | Leader: <?= htmlspecialchars($row['leader_name'] ?? 'Unknown') ?></div>
            <div class="task-actions mt-2">
                <button class="btn btn-warning btn-sm revert-btn" data-id="<?= $row['id'] ?>"><i class="bi bi-arrow-counterclockwise"></i> Revert</button>
                <?php if((int)$row['leader_id'] === (int)$user_id): ?>
                    <button class="btn btn-danger btn-sm delete-btn" data-id="<?= $row['id'] ?>"><i class="bi bi-trash"></i> Delete</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; else: ?>
            <p class="text-muted">No completed tasks.</p>
        <?php endif; ?>
    </div>
</main>

<!-- Edit Modal -->
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
$(function(){
    // Sidebar toggle
    const sidebar = $("#sidebar"), main = $("#main");
    $("#toggleSidebar").click(()=>{sidebar.toggleClass("collapsed"); main.toggleClass("collapsed");});

    // Profile menu
    const profileBtn = $("#profileBtn"), profileMenu = $("#profileMenu");
    profileBtn.click(()=>profileMenu.toggleClass("active"));
    $(window).click(e=>{if(!profileBtn.is(e.target) && profileMenu.has(e.target).length === 0) profileMenu.removeClass("active");});

    // Add task
    $("#addTaskForm").submit(function(e){
        e.preventDefault();
        $.post("group_task_actions.php", $(this).serialize()+"&action=add", res=>{
            if(res.success) location.reload(); else alert(res.message || "Error");
        }, "json");
    });

    // Complete task
    $(document).on("click", ".complete-btn", function(){
        $.post("group_task_actions.php", {id: $(this).data("id"), action:"complete"}, ()=>location.reload(), "json");
    });

    // Revert task
    $(document).on("click", ".revert-btn", function(){
        $.post("group_task_actions.php", {id: $(this).data("id"), action:"revert"}, ()=>location.reload(), "json");
    });

    // Edit task modal
    $(document).on("click", ".edit-btn", function(){
        $("#edit_task_id").val($(this).data("id"));
        $("#edit_title").val($(this).data("title"));
        $("#edit_description").val($(this).data("desc"));
        $("#edit_due_date").val($(this).data("date"));
        new bootstrap.Modal("#editTaskModal").show();
    });

    $("#editTaskForm").submit(function(e){
        e.preventDefault();
        $.post("group_task_actions.php", $(this).serialize()+"&action=edit", ()=>location.reload(), "json");
    });

    // Delete task
    $(document).on("click", ".delete-btn", function(){
        if(!confirm("Delete this task?")) return;
        $.post("group_task_actions.php", {id: $(this).data("id"), action:"delete"}, res=>{
            if(res.success) $("#task-wrapper-"+$(this).data("id")).fadeOut();
        }, "json");
    });

    // Search filter
    $("#searchBox").on("input", function(){
        const term = $(this).val().toLowerCase();
        $(".task-card").each(function(){
            const title = $(this).find(".task-title").text().toLowerCase();
            $(this).toggle(title.includes(term));
        });
    });
});
</script>
</body>
</html>