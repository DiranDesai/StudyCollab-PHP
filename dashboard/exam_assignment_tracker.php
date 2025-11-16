<?php
// exam_assignment_tracker.php
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

$activePage = 'assignments';
$page_title = 'Exam & Assignment Tracker';

// Fetch user assessments
$stmt = $conn->prepare("SELECT id, title, type, course, due_date, status FROM assessments WHERE user_id=? ORDER BY due_date ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$assessments = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Generate content for layout
ob_start();
?>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title">Your Exams & Assignments</h5>
        <?php if(empty($assessments)): ?>
            <div class="text-muted">No exams or assignments added yet.</div>
        <?php else: ?>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Course</th>
                        <th>Due Date & Time</th>
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
                            <td><?= date("M d, Y", strtotime($a['due_date'])) ?>
                                
                            </td>
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
    </div>
</div>
<button class="btn btn-primary mb-3" id="addAssessmentBtn"><i class="bi bi-plus-circle me-1"></i> Add Exam / Assignment</button>

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

<script>
const addBtn = document.getElementById('addAssessmentBtn');
const modalEl = document.getElementById('assessmentModal');
const modal = new bootstrap.Modal(modalEl);

addBtn.addEventListener('click', ()=>{
    document.getElementById('assessmentForm').reset();
    document.getElementById('assessmentAction').value = 'create';
    document.getElementById('modalTitle').textContent = 'Add Exam / Assignment';
    modal.show();
});

// TODO: Add JS for Edit/Delete using fetch/ajax with save_assessment.php
</script>
<?php
$content = ob_get_clean();
include 'layout.php';
