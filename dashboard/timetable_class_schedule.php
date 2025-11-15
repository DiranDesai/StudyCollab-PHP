<?php
// timetable_class_schedule.php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$user_name  = $_SESSION['name'] ?? 'Student';
$first_name = explode(' ', trim($user_name))[0];
$profile_photo = $_SESSION['profile_photo'] ?? null;

// Fetch user's classes for initial rendering (we'll also fetch via AJAX in JS)
$stmt = $conn->prepare("SELECT id, user_id, title, course, location, `day`, start_time, end_time, created_at FROM classes WHERE user_id = ?");
if (!$stmt) {
    // prepare failed - fall back to simple query (rare)
    $classes = [];
} else {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $classes = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Timetable / Class Schedule | StudyCollabo</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />

<style>
:root{--primary:#1a73e8;--bg:#f8f9fa;--surface:#fff;--text:#222}
body{background:var(--bg);color:var(--text);font-family:Inter,system-ui,Arial;margin:0;overflow-x:hidden;}
main{margin-left:250px;padding:90px 28px 40px;transition:margin-left .3s}
@media(max-width:768px){ main{margin-left:0!important;padding-top:80px} }

/* Timetable container */
.timetable-header { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; }
.fc .fc-toolbar-title { font-size:1.15rem; font-weight:600; }
.card-surface { background:var(--surface); border:1px solid #e8e8e8; border-radius:10px; padding:12px; box-shadow:0 2px 8px rgba(18,18,18,0.03); }

/* Event styling in list (when needed) */
.event-item { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:8px; border-bottom:1px solid #f1f1f1; }
.small-muted{color:#6b7280;font-size:.9rem}
</style>
</head>
<body>

<?php include '../includes/sidebar.php' ?>

<main>
    <?php include '../includes/navbar.php' ?>

    <div class="timetable-header">
        <div>
            <h4 class="m-0">Timetable / Class Schedule</h4>
            <small class="text-muted">Add your weekly classes — they will appear on your calendar.</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" id="toggleTimetableOverlayBtn"><i class="bi bi-eye"></i> Toggle Timetable Overlay</button>
            <button class="btn btn-primary" id="openAddModalBtn"><i class="bi bi-plus-circle me-1"></i> Add Class</button>
        </div>
    </div>

    <div class="card-surface">
        <div id="calendar"></div>
    </div>

    <!-- Small list view under calendar -->
    <div class="mt-3 card-surface">
        <h6 class="mb-2">Your Weekly Classes</h6>
        <div id="classList">
            <?php if (empty($classes)): ?>
                <div class="text-muted">No classes added yet.</div>
            <?php else: foreach ($classes as $c): ?>
                <div class="event-item" data-id="<?= intval($c['id']) ?>">
                    <div>
                        <div style="font-weight:600"><?= htmlspecialchars($c['title']) ?></div>
                        <div class="small-muted"><?= htmlspecialchars($c['course']) ?> — <?= htmlspecialchars($c['day']) ?> <?= htmlspecialchars($c['start_time']) ?> - <?= htmlspecialchars($c['end_time']) ?></div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary editClassBtn" data-id="<?= intval($c['id']) ?>">Edit</button>
                        <button class="btn btn-sm btn-danger deleteClassBtn" data-id="<?= intval($c['id']) ?>">Delete</button>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</main>

<!-- Add / Edit Modal -->
<div class="modal fade" id="classModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form id="classForm" class="modal-content">
      <input type="hidden" name="action" id="formAction" value="create">
      <input type="hidden" name="id" id="classId" value="">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Add Class</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2">
            <div class="col-md-8">
                <label class="form-label">Class Title <small class="text-muted">(e.g. Web Programming Lecture)</small></label>
                <input name="title" id="title" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Course</label>
                <input name="course" id="course" class="form-control">
            </div>

            <div class="col-md-6">
                <label class="form-label">Day</label>
                <select name="day" id="day" class="form-select" required>
                    <option value="">Select day</option>
                    <option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option><option>Saturday</option><option>Sunday</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Start Time</label>
                <input type="time" name="start_time" id="start_time" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">End Time</label>
                <input type="time" name="end_time" id="end_time" class="form-control" required>
            </div>

            <div class="col-md-8">
                <label class="form-label">Location (optional)</label>
                <input name="location" id="location" class="form-control">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="addToCalendar" name="addToCalendar" checked>
                    <label class="form-check-label" for="addToCalendar">Also add to Calendar</label>
                </div>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="revertDeleteBtn" class="btn btn-warning me-auto" style="display:none;">Undo Last Delete</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="saveClassBtn">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Undo toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1055;">
  <div id="undoToast" class="toast align-items-center text-bg-dark" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">Class deleted. <button id="undoToastBtn" class="btn btn-sm btn-link text-white">Undo</button></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- FullCalendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
(() => {
    const calendarEl = document.getElementById('calendar');
    const openAddModalBtn = document.getElementById('openAddModalBtn');
    const classModalEl = document.getElementById('classModal');
    const classModal = new bootstrap.Modal(classModalEl);
    const classForm = document.getElementById('classForm');
    const modalTitle = document.getElementById('modalTitle');
    const formAction = document.getElementById('formAction');
    const revertDeleteBtn = document.getElementById('revertDeleteBtn');

    // Toast
    const undoToastEl = document.getElementById('undoToast');
    const undoToast = new bootstrap.Toast(undoToastEl);

    // Calendar instance
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        firstDay: 1, // Monday
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridWeek,dayGridMonth'
        },
        slotMinTime: "06:00:00",
        slotMaxTime: "22:00:00",
        allDaySlot: false,
        events: fetchEvents,
        eventClick: function(info) {
            // open edit modal
            openEditModal(info.event.extendedProps.rawId || info.event.id);
        },
        nowIndicator: true,
        height: 650
    });
    calendar.render();

    // fetch events returns events array
    function fetchEvents(fetchInfo, successCallback, failureCallback) {
        fetch('get_classes.php')
            .then(r => r.json())
            .then(json => {
                if (!json.success) {
                    failureCallback(json.message || 'Failed to load classes');
                    return;
                }
                const events = json.data.map(c => {
                    // map day to next occurrence date for display within the week
                    const dayMap = { 'Sunday':0,'Monday':1,'Tuesday':2,'Wednesday':3,'Thursday':4,'Friday':5,'Saturday':6 };
                    const dow = dayMap[c.day] ?? 1;
                    // FullCalendar accepts recurring events; but we will map to a date in the current week based on fetchInfo.start
                    // compute a date within visible range that matches the day of week:
                    const rangeStart = new Date(fetchInfo.start);
                    const startOfWeek = new Date(rangeStart);
                    startOfWeek.setDate(rangeStart.getDate() - ((rangeStart.getDay() + 6) % 7)); // Monday as start
                    // compute date
                    const eventDate = new Date(startOfWeek);
                    eventDate.setDate(startOfWeek.getDate() + (dow - 1));
                    // attach times
                    const startParts = c.start_time.split(':');
                    const endParts = c.end_time.split(':');
                    eventDate.setHours(parseInt(startParts[0],10), parseInt(startParts[1]||0,10), 0, 0);
                    const endDate = new Date(eventDate);
                    endDate.setHours(parseInt(endParts[0],10), parseInt(endParts[1]||0,10), 0, 0);

                    return {
                        id: c.id,
                        title: c.title + (c.course ? "\\n" + c.course : ""),
                        start: eventDate.toISOString(),
                        end: endDate.toISOString(),
                        extendedProps: {
                            course: c.course,
                            location: c.location,
                            rawId: c.id
                        }
                    };
                });
                successCallback(events);
            })
            .catch(err => {
                console.error(err);
                failureCallback(err);
            });
    }

    // reload calendar events and list
    function reloadAll() {
        calendar.refetchEvents();
        loadList();
    }

    // load list below calendar
    function loadList() {
        fetch('get_classes.php').then(r=>r.json()).then(json=>{
            const container = document.getElementById('classList');
            container.innerHTML = '';
            if(!json.success || !Array.isArray(json.data) || json.data.length === 0) {
                container.innerHTML = '<div class="text-muted">No classes added yet.</div>';
                return;
            }
            json.data.forEach(c => {
                const wrap = document.createElement('div');
                wrap.className = 'event-item';
                wrap.dataset.id = c.id;
                wrap.innerHTML = `
                    <div>
                        <div style="font-weight:600">${escapeHtml(c.title)}</div>
                        <div class="small-muted">${escapeHtml(c.course)} — ${escapeHtml(c.day)} ${escapeHtml(c.start_time)} - ${escapeHtml(c.end_time)}</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary editClassBtn" data-id="${c.id}">Edit</button>
                        <button class="btn btn-sm btn-danger deleteClassBtn" data-id="${c.id}">Delete</button>
                    </div>
                `;
                container.appendChild(wrap);
            });
        });
    }

    // helper
    function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    // Open add modal
    openAddModalBtn.addEventListener('click', ()=> {
        formAction.value = 'create';
        document.getElementById('classId').value = '';
        modalTitle.textContent = 'Add Class';
        classForm.reset();
        document.getElementById('addToCalendar').checked = true;
        revertDeleteBtn.style.display = 'none';
        classModal.show();
    });

    // Edit modal opener
    document.addEventListener('click', function(e){
        const btn = e.target.closest('.editClassBtn');
        if (!btn) return;
        const id = btn.getAttribute('data-id');
        openEditModal(id);
    });

    // Delete button in list
    document.addEventListener('click', function(e){
        const btn = e.target.closest('.deleteClassBtn');
        if (!btn) return;
        const id = btn.getAttribute('data-id');
        doDelete(id);
    });

    // open edit modal given id
    function openEditModal(id) {
        fetch('get_classes.php?id=' + encodeURIComponent(id))
            .then(r=>r.json())
            .then(json=>{
                if(!json.success || !json.data || !json.data[0]) { alert('Class not found'); return; }
                const c = json.data[0];
                formAction.value = 'update';
                document.getElementById('classId').value = c.id;
                document.getElementById('title').value = c.title;
                document.getElementById('course').value = c.course;
                document.getElementById('location').value = c.location;
                document.getElementById('day').value = c.day;
                document.getElementById('start_time').value = c.start_time;
                document.getElementById('end_time').value = c.end_time;
                modalTitle.textContent = 'Edit Class';
                revertDeleteBtn.style.display = 'none';
                classModal.show();
            }).catch(err=>{ console.error(err); alert('Failed to fetch class'); });
    }

    // form submit -> create/update
    classForm.addEventListener('submit', function(e){
        e.preventDefault();
        const fd = new FormData(classForm);
        fetch('save_class.php', { method:'POST', body:fd })
            .then(r=>r.json())
            .then(json=>{
                if(json.success){
                    classModal.hide();
                    reloadAll();
                    if (json.message) alert(json.message);
                } else {
                    alert(json.message || 'Save failed');
                }
            }).catch(err=>{ console.error(err); alert('Save error'); });
    });

    // delete flow with session-based undo
    function doDelete(id) {
        if(!confirm('Delete class? This can be undone via the Undo button for this session.')) return;
        const fd = new FormData();
        fd.append('action','delete');
        fd.append('id', id);
        fetch('save_class.php',{ method:'POST', body:fd })
            .then(r=>r.json())
            .then(json=>{
                if(json.success){
                    reloadAll();
                    // show toast with undo
                    undoToast.show();
                    // attach undo button listener
                    document.getElementById('undoToastBtn').onclick = function(){
                        // call undo endpoint
                        const fd2 = new FormData();
                        fd2.append('action','undo');
                        fetch('save_class.php',{method:'POST', body:fd2})
                          .then(r=>r.json())
                          .then(j=>{
                              if(j.success){
                                  reloadAll();
                                  undoToast.hide();
                                  alert('Delete undone');
                              } else {
                                  alert(j.message || 'Undo failed');
                              }
                          }).catch(err=>{ console.error(err); alert('Undo error'); });
                    };
                } else {
                    alert(json.message || 'Delete failed');
                }
            }).catch(err=>{ console.error(err); alert('Delete error'); });
    }

    // Toggle overlay: this example toggles a CSS class that dims the calendar a little to highlight overlay — placeholder
    document.getElementById('toggleTimetableOverlayBtn').addEventListener('click', function(){
        calendarEl.classList.toggle('timetable-overlay');
    });

    // initial load
    loadList();
})();
</script>
</body>
</html>