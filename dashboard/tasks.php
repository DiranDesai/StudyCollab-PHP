<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id    = intval($_SESSION['user_id']);
$user_name  = $_SESSION['name'] ?? 'Student';
$user_email = $_SESSION['email'] ?? 'student@example.com';
$first_name = explode(' ', trim($user_name))[0];
$profile_photo = $_SESSION['profile_photo'] ?? null;

// ---------- AJAX / POST Handlers ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'];

    // ADD TASK
    if ($action === 'add_task') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $due_date = $_POST['due_date'] ?? '';

        if (empty($title) || empty($due_date)) {
            echo json_encode(['success' => false, 'error' => 'Please fill required fields.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, description, due_date, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'Pending', NOW(), NOW())");
        $stmt->bind_param('isss', $user_id, $title, $description, $due_date);
        if ($stmt->execute()) {
            $newId = $stmt->insert_id;
            echo json_encode([
                'success' => true,
                'task' => [
                    'id' => $newId,
                    'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
                    'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
                    'due_date' => $due_date,
                    'status' => 'Pending'
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'DB error while adding task.']);
        }
        $stmt->close();
        exit;
    }

    // COMPLETE / REVERT TASK
    if ($action === 'toggle_complete') {
        $id = intval($_POST['id'] ?? 0);
        $target = $_POST['target'] === 'Completed' ? 'Completed' : 'Pending';

        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE tasks SET status = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->bind_param('sii', $target, $id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'id' => $id, 'status' => $target]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Unable to update task (not found or no change).']);
        }
        $stmt->close();
        exit;
    }

    // DELETE TASK
    if ($action === 'delete_task') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'id' => $id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Unable to delete task (not found).']);
        }
        $stmt->close();
        exit;
    }

    // EDIT TASK (update)
    if ($action === 'edit_task') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $due_date = $_POST['due_date'] ?? '';

        if ($id <= 0 || empty($title) || empty($due_date)) {
            echo json_encode(['success' => false, 'error' => 'Missing/invalid fields']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE tasks SET title = ?, description = ?, due_date = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->bind_param('sssii', $title, $description, $due_date, $id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            echo json_encode([
                'success' => true,
                'task' => [
                    'id' => $id,
                    'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
                    'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
                    'due_date' => $due_date
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Unable to update task.']);
        }
        $stmt->close();
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}
// ---------- END POST HANDLERS ----------

// Fetch tasks for display
$pendingTasks = $conn->query("SELECT * FROM tasks WHERE user_id={$user_id} AND status='Pending' ORDER BY due_date ASC");
$completedTasks = $conn->query("SELECT * FROM tasks WHERE user_id={$user_id} AND status='Completed' ORDER BY due_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Tasks | StudyCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root { --primary:#1a73e8; --surface:#fff;  --bg:#f5f5f7; --text:#202124; --muted:#5f6368; }
body { 
    font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto;
    background:var(--surface);
overflow-x:hidden; }

/* Sidebar */
.sidebar { width:250px; height:100vh; background:var(--surface); border-right:1px solid #ddd; position:fixed; top:0; left:0; transition: width 0.3s; overflow:hidden; }
.sidebar.collapsed { width:80px; }
.sidebar .logo { font-size:1.3rem; font-weight:600; color:var(--primary); padding:20px; display:flex; align-items:center; gap:8px; }
.sidebar ul { list-style:none; padding:0; margin:0; }
.sidebar ul li a { display:flex; align-items:center; gap:15px; padding:12px 20px; color:#333; text-decoration:none; font-weight:500; border-radius:10px; margin:5px 10px; transition:0.3s; }
.sidebar ul li a:hover, .sidebar ul li a.active { background:#e8f0fe; color:var(--primary); }
.sidebar.collapsed ul li a span { display:none; }

/* Topbar */
.topbar { display:flex; justify-content:space-between; align-items:center; gap:8px; height:64px; background: var(--surface); padding: 0 20px; border-bottom:1px solid #ddd; position: fixed; top:0; left:0; width:100%; z-index:99; }
.top-left { display:flex; align-items:center; gap:10px; }
.top-left img { height:32px; }
.search-box { position:relative; width:320px; }
.search-box input { width:100%; padding:8px 35px; border-radius:25px; border:1px solid #ddd; background:#f1f3f4; }
.search-box i { position:absolute; top:8px; left:12px; color:#888; }

/* Profile */
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

/* Card */
.card-apple {
    background:var(--card);
    padding:22px;
    border-radius:var(--radius);
    border:1px solid #e5e5e7;
    box-shadow:0 4px 20px rgba(0,0,0,0.04);
    transition:all 0.25s ease;
}

/* Main */
main { margin-left:250px; padding:90px 30px 40px; transition: margin-left 0.3s; }
main.collapsed { margin-left:80px; }

/* Task Cards */
.task-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
    margin-top: 20px;
}

.task-card {
    background: var(--surface);
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.2s, box-shadow 0.2s;
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

.task-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.task-title { font-weight:600; font-size:1.1rem; margin-bottom:5px; }
.task-desc { color:#555; margin-bottom:10px; }
.task-meta { font-size:0.85rem; color:#888; margin-bottom:10px; }
.task-actions { display:flex; gap:8px; flex-wrap:wrap; }
.task-actions .btn { flex:1; font-size:0.85rem; border-radius:8px; }

/* Buttons */
.btn-add { background: var(--primary); color:#fff; }
.btn-complete { background:#198754; color:#fff; }
.btn-edit { background:#ffc107; color:#fff; }
.btn-delete { background:#dc3545; color:#fff; }

.text-decoration-line-through { text-decoration: line-through; color:#555; }

.task-section-title { font-size:1rem; font-weight:600; color:var(--primary); margin-bottom:15px; }

/* Responsive */
@media(max-width:768px){
    main{margin-left:0 !important; padding-top:80px;}
    .sidebar{display:none;}
    .task-cards{grid-template-columns:1fr;}
}
</style>
</head>
<body>

<?php include '../includes/sidebar.php' ?>

<!-- Main -->
<main id="main">
   <?php include '../includes/navbar.php' ?>
 
    <!-- Add Task Form -->
    <!-- <div class="task-section-title">Add New Task</div> -->
    <!-- Task Card -->
<div class="card shadow-sm p-4 mt-10 task-card" style="max-width: 500px; margin:2rem auto;">
    <h5 class="fw-semibold mb-3">Add Task</h5>
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
        <button type="submit" class="font-bold btn btn-primary w-100 d-flex align-items-center justify-content-center">
            <i class="bi bi-plus-circle me-2"></i><span class="font-semibold">Add Task</span>
        </button>
    </form>
</div>


    <!-- Pending Tasks -->
    <div class="task-section-title mt-4">Pending Tasks</div>
    <div class="task-cards" id="pendingTasksContainer">
        <?php if($pendingTasks && $pendingTasks->num_rows > 0): ?>
            <?php while($row = $pendingTasks->fetch_assoc()): ?>
            <div class="card-apple rounded-4" id="task-wrapper-<?= $row['id'] ?>">
                <div class="task-title"><?= htmlspecialchars($row['title']) ?></div>
                <div class="task-desc"><?= htmlspecialchars($row['description']) ?></div>
                <div class="task-meta">Due: <?= $row['due_date'] ?> | <span class="text-warning fw-semibold">Pending</span></div>
                <div class="task-actions">
                    <button class="btn btn-complete btn-sm complete-btn" data-id="<?= $row['id'] ?>"><i class="bi bi-check-lg"></i> Complete</button>
                    <button class="btn btn-edit btn-sm edit-btn" data-id="<?= $row['id'] ?>" data-title="<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>" data-desc="<?= htmlspecialchars($row['description'], ENT_QUOTES) ?>" data-date="<?= $row['due_date'] ?>"><i class="bi bi-pencil"></i> Edit</button>
                    <button class="btn btn-delete btn-sm delete-btn" data-id="<?= $row['id'] ?>"><i class="bi bi-trash"></i> Delete</button>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted" id="noPending">No pending tasks.</p>
        <?php endif; ?>
    </div>

    <div class="task-section-title mt-4">Completed Tasks</div>
    <div class="task-cards" id="completedTasksContainer">
        <?php if($completedTasks && $completedTasks->num_rows > 0): ?>
            <?php while($row = $completedTasks->fetch_assoc()): ?>
            <div class="task-card bg-light" id="task-wrapper-<?= $row['id'] ?>">
                <div class="task-title text-decoration-line-through"><?= htmlspecialchars($row['title']) ?></div>
                <div class="task-desc"><?= htmlspecialchars($row['description']) ?></div>
                <div class="task-meta">Due: <?= $row['due_date'] ?> | <span class="text-success fw-semibold">Completed</span></div>
                <div class="task-actions">
                    <button class="btn btn-complete btn-sm complete-btn" data-id="<?= $row['id'] ?>" data-revert="1"><i class="bi bi-arrow-counterclockwise"></i> Revert</button>
                    <button class="btn btn-edit btn-sm edit-btn" data-id="<?= $row['id'] ?>" data-title="<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>" data-desc="<?= htmlspecialchars($row['description'], ENT_QUOTES) ?>" data-date="<?= $row['due_date'] ?>"><i class="bi bi-pencil"></i> Edit</button>
                    <button class="btn btn-delete btn-sm delete-btn" data-id="<?= $row['id'] ?>"><i class="bi bi-trash"></i> Delete</button>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-muted" id="noCompleted">No completed tasks.</p>
        <?php endif; ?>
    </div>
</main>

<!-- Edit Modal (Bootstrap) -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editTaskForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editTaskLabel">Edit Task</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id" id="editTaskId">
            <div class="mb-3">
                <input type="text" name="title" id="editTitle" class="form-control" placeholder="Task Title" required>
            </div>
            <div class="mb-3">
                <textarea name="description" id="editDescription" class="form-control" rows="3" placeholder="Description (optional)"></textarea>
            </div>
            <div class="mb-3">
                <input type="date" name="due_date" id="editDueDate" class="form-control" required>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-add">Save changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar toggle
const sidebar = document.getElementById('sidebar');
const toggleSidebar = document.getElementById('toggleSidebar');
const profileBtn = document.getElementById('profileBtn');
const profileMenu = document.getElementById('profileMenu');

toggleSidebar.addEventListener('click',()=>{
    sidebar.classList.toggle('collapsed');
    document.getElementById('main').classList.toggle('collapsed');
});

// Profile dropdown
profileBtn.addEventListener('click', e=>{
    e.stopPropagation();
    profileMenu.classList.toggle('active');
});
document.addEventListener('click', e=>{
    if(!profileMenu.contains(e.target)) profileMenu.classList.remove('active');
});

// Helper: create task card HTML (keeps same structure & buttons)
function createTaskCard(task) {
    const wrapper = document.createElement('div');
    wrapper.className = 'task-card';
    wrapper.id = `task-wrapper-${task.id}`;

    const title = document.createElement('div');
    title.className = 'task-title';
    title.innerText = task.title;

    const desc = document.createElement('div');
    desc.className = 'task-desc';
    desc.innerText = task.description;

    const meta = document.createElement('div');
    meta.className = 'task-meta';
    meta.innerHTML = `Due: ${task.due_date} | <span class="${task.status === 'Completed' ? 'text-success' : 'text-warning'} fw-semibold">${task.status}</span>`;

    const actions = document.createElement('div');
    actions.className = 'task-actions';

    // Complete / Revert button
    const completeBtn = document.createElement('button');
    completeBtn.className = 'btn btn-complete btn-sm complete-btn';
    completeBtn.dataset.id = task.id;
    completeBtn.innerHTML = task.status === 'Completed' ? '<i class="bi bi-arrow-counterclockwise"></i> Revert' : '<i class="bi bi-check-lg"></i> Complete';
    actions.appendChild(completeBtn);

    // Edit
    const editBtn = document.createElement('button');
    editBtn.className = 'btn btn-edit btn-sm edit-btn';
    editBtn.dataset.id = task.id;
    editBtn.dataset.title = task.title;
    editBtn.dataset.desc = task.description;
    editBtn.dataset.date = task.due_date;
    editBtn.innerHTML = '<i class="bi bi-pencil"></i> Edit';
    actions.appendChild(editBtn);

    // Delete
    const deleteBtn = document.createElement('button');
    deleteBtn.className = 'btn btn-delete btn-sm delete-btn';
    deleteBtn.dataset.id = task.id;
    deleteBtn.innerHTML = '<i class="bi bi-trash"></i> Delete';
    actions.appendChild(deleteBtn);

    wrapper.appendChild(title);
    wrapper.appendChild(desc);
    wrapper.appendChild(meta);
    wrapper.appendChild(actions);

    if (task.status === 'Completed') {
        wrapper.classList.add('bg-light');
        title.classList.add('text-decoration-line-through');
    }

    return wrapper;
}

// ---------- Add Task (AJAX) ----------
document.getElementById('addTaskForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    formData.append('action', 'add_task');

    try {
        const res = await fetch('tasks.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            const task = data.task;
            const card = createTaskCard(task);
            // remove 'noPending' message if present
            const noPending = document.getElementById('noPending');
            if (noPending) noPending.remove();

            const pendingContainer = document.getElementById('pendingTasksContainer');
            pendingContainer.prepend(card);
            form.reset();
        } else {
            alert(data.error || 'Failed to add task');
        }
    } catch (err) {
        console.error(err);
        alert('Network error');
    }
});

// ---------- Event delegation for complete/edit/delete ----------
document.addEventListener('click', async (e) => {
    // COMPLETE / REVERT
    if (e.target.closest('.complete-btn')) {
        const btn = e.target.closest('.complete-btn');
        const id = btn.dataset.id;
        // Determine target status (if revert button exists, will be revert)
        const parentCard = document.getElementById(`task-wrapper-${id}`);
        const isCompleted = parentCard && parentCard.querySelector('.task-title').classList.contains('text-decoration-line-through');
        const target = isCompleted ? 'Pending' : 'Completed';

        if (!confirm(target === 'Completed' ? 'Mark this task as completed?' : 'Revert this task to pending?')) return;

        const fd = new FormData();
        fd.append('action', 'toggle_complete');
        fd.append('id', id);
        fd.append('target', target);

        try {
            const res = await fetch('tasks.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                // move DOM node between containers
                const card = document.getElementById(`task-wrapper-${id}`);
                if (card) {
                    // update meta and styles
                    const meta = card.querySelector('.task-meta');
                    if (meta) meta.innerHTML = `Due: ${meta.innerHTML.split('|')[0].replace('Due:', '').trim()} | <span class="${target === 'Completed' ? 'text-success' : 'text-warning'} fw-semibold">${target}</span>`;

                    const title = card.querySelector('.task-title');
                    if (target === 'Completed') {
                        card.classList.add('bg-light');
                        title.classList.add('text-decoration-line-through');
                        // update complete button text
                        const completeBtn = card.querySelector('.complete-btn');
                        if (completeBtn) completeBtn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> Revert';
                        document.getElementById('completedTasksContainer').prepend(card);
                        // remove noCompleted msg if present
                        const noCompleted = document.getElementById('noCompleted');
                        if (noCompleted) noCompleted.remove();
                    } else {
                        card.classList.remove('bg-light');
                        title.classList.remove('text-decoration-line-through');
                        const completeBtn = card.querySelector('.complete-btn');
                        if (completeBtn) completeBtn.innerHTML = '<i class="bi bi-check-lg"></i> Complete';
                        document.getElementById('pendingTasksContainer').prepend(card);
                        const noPending = document.getElementById('noPending');
                        if (noPending) noPending.remove();
                    }
                }
            } else {
                alert(data.error || 'Could not update task status.');
            }
        } catch (err) {
            console.error(err);
            alert('Network error');
        }
    }

    // DELETE
    if (e.target.closest('.delete-btn')) {
        const btn = e.target.closest('.delete-btn');
        const id = btn.dataset.id;
        if (!confirm('Delete this task? This cannot be undone.')) return;

        const fd = new FormData();
        fd.append('action', 'delete_task');
        fd.append('id', id);

        try {
            const res = await fetch('tasks.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                const card = document.getElementById(`task-wrapper-${id}`);
                if (card) card.remove();
            } else {
                alert(data.error || 'Could not delete task.');
            }
        } catch (err) {
            console.error(err);
            alert('Network error');
        }
    }

    // OPEN EDIT MODAL
    if (e.target.closest('.edit-btn')) {
        const btn = e.target.closest('.edit-btn');
        const id = btn.dataset.id;
        const title = btn.dataset.title ?? '';
        const desc = btn.dataset.desc ?? '';
        const date = btn.dataset.date ?? '';

        document.getElementById('editTaskId').value = id;
        document.getElementById('editTitle').value = title;
        document.getElementById('editDescription').value = desc;
        document.getElementById('editDueDate').value = date;

        const editModal = new bootstrap.Modal(document.getElementById('editTaskModal'));
        editModal.show();
    }
});

// ---------- Edit form submit ----------
document.getElementById('editTaskForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const fd = new FormData(form);
    fd.append('action', 'edit_task');

    try {
        const res = await fetch('tasks.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            const t = data.task;
            const card = document.getElementById(`task-wrapper-${t.id}`);
            if (card) {
                const titleEl = card.querySelector('.task-title');
                const descEl = card.querySelector('.task-desc');
                const metaEl = card.querySelector('.task-meta');

                if (titleEl) titleEl.innerText = t.title;
                if (descEl) descEl.innerText = t.description;
                if (metaEl) {
                    // preserve status part
                    const statusPart = metaEl.innerHTML.split('|').pop();
                    metaEl.innerHTML = `Due: ${t.due_date} | ${statusPart}`;
                }

                // Also update data attributes on edit button for future edits
                const editBtn = card.querySelector('.edit-btn');
                if (editBtn) {
                    editBtn.dataset.title = t.title;
                    editBtn.dataset.desc = t.description;
                    editBtn.dataset.date = t.due_date;
                }
            }

            // hide modal
            const editModalEl = document.getElementById('editTaskModal');
            const bsModal = bootstrap.Modal.getInstance(editModalEl);
            if (bsModal) bsModal.hide();
        } else {
            alert(data.error || 'Could not update task.');
        }
    } catch (err) {
        console.error(err);
        alert('Network error');
    }
});

// Optional: simple search filter (client-side)
document.getElementById('searchBox').addEventListener('input', (e) => {
    const q = e.target.value.toLowerCase();
    const allCards = document.querySelectorAll('.task-card');
    allCards.forEach(card => {
        const title = (card.querySelector('.task-title')?.innerText || '').toLowerCase();
        const desc = (card.querySelector('.task-desc')?.innerText || '').toLowerCase();
        card.style.display = (title.includes(q) || desc.includes(q)) ? '' : 'none';
    });
});
</script>
</body>
</html>