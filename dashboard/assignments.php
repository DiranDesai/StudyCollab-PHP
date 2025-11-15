<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_name  = $_SESSION['name'] ?? 'Student';
$first_name = explode(' ', trim($user_name))[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assessments | StudyCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<style>
:root{--primary:#1a73e8;--bg:#f8f9fa;--surface:#fff;--text:#222;}
body{background:var(--bg);color:var(--text);font-family:Inter,Arial,sans-serif;margin:0;}
main{margin-left:250px;padding:90px 30px 40px;}
.card-surface{background:var(--surface);border:1px solid #ddd;border-radius:10px;padding:15px;box-shadow:0 2px 8px rgba(0,0,0,0.05);}
.table th, .table td{vertical-align:middle;}
.toast-container{position:fixed;bottom:1rem;right:1rem;z-index:1100;}
</style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>
<?php include '../includes/navbar.php'; ?>

<main>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Assessments</h4>
        <button class="btn btn-primary" id="openAddModalBtn"><i class="bi bi-plus-circle me-1"></i> Add Assessment</button>
    </div>

    <div class="card-surface mb-4">
        <table class="table table-striped" id="assessmentTable">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Course</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="card-surface">
        <div id="calendar"></div>
    </div>
</main>

<!-- Add/Edit Modal -->
<div class="modal fade" id="assessmentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form id="assessmentForm" class="modal-content">
      <input type="hidden" name="id" id="assessmentId">
      <div class="modal-header">
        <h5 class="modal-title">Add Assessment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-2">
        <div class="col-md-4">
            <label class="form-label">Type</label>
            <select name="type" id="type" class="form-select" required>
                <option value="">Select</option>
                <option>Exam</option>
                <option>Assignment</option>
            </select>
        </div>
        <div class="col-md-8">
            <label class="form-label">Title</label>
            <input type="text" name="title" id="title" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Course</label>
            <input type="text" name="course" id="course" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">Due Date</label>
            <input type="datetime-local" name="due_date" id="due_date" class="form-control" required>
        </div>
        <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" id="notes" class="form-control"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Assessment</button>
      </div>
    </form>
  </div>
</div>

<div class="toast-container" id="reminderToastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
(() => {
    const tableBody = document.querySelector('#assessmentTable tbody');
    const modal = new bootstrap.Modal(document.getElementById('assessmentModal'));
    const form = document.getElementById('assessmentForm');
    const openAddBtn = document.getElementById('openAddModalBtn');
    let assessments = [];

    // Fetch and render assessments
    function loadAssessments(){
        fetch('get_assessments.php')
            .then(r=>r.json())
            .then(json=>{
                if(!json.success) return;
                assessments = json.data;
                renderTable();
                renderCalendar();
                showReminders();
            });
    }

    function renderTable(){
        tableBody.innerHTML = '';
        assessments.forEach(a=>{
            const due = new Date(a.due_date);
            const now = new Date();
            const status = due < now ? 'Overdue' : 'Upcoming';
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${a.type}</td>
                <td>${a.title}</td>
                <td>${a.course || '-'}</td>
                <td>${due.toLocaleString()}</td>
                <td>${status}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary editBtn" data-id="${a.id}"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-danger deleteBtn" data-id="${a.id}"><i class="bi bi-trash"></i></button>
                </td>
            `;
            tableBody.appendChild(tr);
        });
    }

    // Calendar
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl,{
        initialView:'timeGridWeek',
        headerToolbar:{left:'prev,next today',center:'title',right:'timeGridWeek,dayGridMonth'},
        events: ()=>assessments.map(a=>{
            const start = new Date(a.due_date);
            const end = new Date(a.due_date);
            end.setHours(end.getHours()+1);
            return {
                title: `${a.type}: ${a.title}`,
                start: start.toISOString(),
                end: end.toISOString(),
                backgroundColor: a.type==='Exam'?'#dc3545':'#0d6efd'
            };
        }),
        height:650
    });
    calendar.render();

    function renderCalendar(){ calendar.refetchEvents(); }

    // Add/Edit modal
    openAddBtn.addEventListener('click',()=>{
        form.reset();
        document.querySelector('#assessmentModal .modal-title').textContent='Add Assessment';
        document.getElementById('assessmentId').value='';
        modal.show();
    });

    tableBody.addEventListener('click',e=>{
        const editBtn = e.target.closest('.editBtn');
        const deleteBtn = e.target.closest('.deleteBtn');
        if(editBtn){
            const id = editBtn.dataset.id;
            const a = assessments.find(x=>x.id==id);
            if(!a) return;
            document.getElementById('assessmentId').value = a.id;
            document.getElementById('type').value = a.type;
            document.getElementById('title').value = a.title;
            document.getElementById('course').value = a.course;
            document.getElementById('due_date').value = a.due_date.replace(' ','T');
            document.getElementById('notes').value = a.notes;
            document.querySelector('#assessmentModal .modal-title').textContent='Edit Assessment';
            modal.show();
        }
        if(deleteBtn){
            const id = deleteBtn.dataset.id;
            if(confirm('Delete this assessment?')){
                fetch('delete_assessment.php',{method:'POST',body:new URLSearchParams({id})})
                .then(r=>r.json()).then(j=>{ if(j.success) loadAssessments(); else alert(j.message); });
            }
        }
    });

    form.addEventListener('submit',e=>{
        e.preventDefault();
        const data = new FormData(form);
        fetch('save_assessment.php',{method:'POST',body:data})
            .then(r=>r.json())
            .then(j=>{
                if(j.success){
                    modal.hide();
                    loadAssessments();
                }else alert(j.message);
            });
    });

    // 24-hour reminders
    function showReminders(){
        const now = new Date();
        const container = document.getElementById('reminderToastContainer');
        container.innerHTML = '';
        assessments.forEach(a=>{
            const due = new Date(a.due_date);
            const diff = due - now;
            if(diff>0 && diff<=24*60*60*1000){
                const toastEl = document.createElement('div');
                toastEl.className='toast align-items-center text-bg-warning mb-2';
                toastEl.setAttribute('role','alert');
                toastEl.setAttribute('aria-live','assertive');
                toastEl.setAttribute('aria-atomic','true');
                toastEl.innerHTML=`
                    <div class="d-flex">
                        <div class="toast-body"><strong>${a.type}</strong>: ${a.title} is due ${due.toLocaleString()}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                container.appendChild(toastEl);
                new bootstrap.Toast(toastEl,{delay:10000}).show();
            }
        });
    }

    loadAssessments();
})();
</script>

</body>
</html>