<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$user_name = $_SESSION['name'] ?? 'Student';
$first_name = explode(' ', trim($user_name))[0];
$profile_photo = $_SESSION['profile_photo'] ?? null;
$user_email = $_SESSION['email'] ?? '';

// Fetch user assessments
$stmt = $conn->prepare("SELECT id, title, type, course, due_date, status FROM assessments WHERE user_id=? ORDER BY due_date ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$assessments = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Exam & Assignment Tracker | StudyCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
    :root{--primary:#1a73e8;--surface:#fff;--bg:#f5f5f7;--text:#202124}
body{font-family:Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; background:var(--bg); color:var(--text); margin:0; overflow-x:hidden;}
main{margin-left:250px;padding:90px 28px 40px;transition:margin-left .3s}
</style>
</head>
<body style="background:#f8f9fa;">
<?php include '../includes/sidebar.php'; ?>
<main>
    <?php include '../includes/navbar.php'; ?>
<div class="container py-4">

    <h2 class="mb-4">Your Exams & Assignments</h2>

    <?php if(empty($assessments)): ?>
        <div class="text-muted">No exams or assignments added yet.</div>
    <?php else: ?>
        <table class="table table-hover bg-white shadow-sm rounded">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Course</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($assessments as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['title']) ?></td>
                    <td><?= htmlspecialchars($a['type']) ?></td>
                    <td><?= htmlspecialchars($a['course']) ?></td>
                    <td><?= date("M d, Y", strtotime($a['due_date'])) ?></td>
                    <td><?= htmlspecialchars($a['status']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary editBtn" data-id="<?= $a['id'] ?>">Edit</button>
                        <button class="btn btn-sm btn-danger deleteBtn" data-id="<?= $a['id'] ?>">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <button class="btn btn-primary mt-3" id="addAssessmentBtn"><i class="bi bi-plus-circle me-1"></i> Add Exam / Assignment</button>

</div>

<!-- Modal for Add/Edit -->
<div class="modal fade" id="assessmentModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="assessmentForm">
      <input type="hidden" name="id" id="assessmentId">
      <input type="hidden" name="action" id="assessmentAction" value="create">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add Exam / Assignment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
            <label class="form-label">Title</label>
            <input type="text" name="title" id="title" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Type</label>
            <select name="type" id="type" class="form-select" required>
                <option value="">Select Type</option>
                <option value="Exam">Exam</option>
                <option value="Assignment">Assignment</option>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Course</label>
            <input type="text" name="course" id="course" class="form-control">
        </div>
        <div class="mb-2">
            <label class="form-label">Due Date</label>
            <input type="date" name="due_date" id="due_date" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Due Time</label>
            <input type="time" name="due_time" id="due_time" class="form-control">
        </div>
        <div class="mb-2">
            <label class="form-label">Status</label>
            <select name="status" id="status" class="form-select">
                <option value="Pending">Pending</option>
                <option value="Completed">Completed</option>
                <option value="Overdue">Overdue</option>
            </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
      </div>
    </form>
  </div>
</div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const addBtn = document.getElementById('addAssessmentBtn');
const modalEl = document.getElementById('assessmentModal');
const modal = new bootstrap.Modal(modalEl);

const profileBtn = document.getElementById('profileBtn');
const profileMenu = document.getElementById('profileMenu');

toggleSidebar.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    document.getElementById('main').classList.toggle('collapsed');
    document.getElementById('topbar').classList.toggle('collapsed');
});

// Profile dropdown
profileBtn.addEventListener('click', e => {
    e.stopPropagation();
    profileMenu.classList.toggle('active');
});

addBtn.addEventListener('click', ()=>{
    document.getElementById('assessmentForm').reset();
    document.getElementById('assessmentAction').value = 'create';
    document.getElementById('modalTitle').textContent = 'Add Exam / Assignment';
    modal.show();
});

// TODO: Add JS for Edit/Delete using fetch/ajax
</script>
</body>
</html>
