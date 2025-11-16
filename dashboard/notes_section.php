<?php
// dashboard/notes.php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id       = intval($_SESSION['user_id']);
$user_name     = $_SESSION['name'] ?? 'Student';
$user_email    = $_SESSION['email'] ?? 'student@example.com';
$first_name    = explode(' ', trim($user_name))[0];
$profile_photo = $_SESSION['profile_photo'] ?? null;

// Page title for navbar (if your navbar uses it)
$page_title = "Notes";

// Fetch user's notes (most recent first)
$notes_stmt = $conn->prepare("SELECT id, title, course, content, created_at FROM notes WHERE user_id = ? ORDER BY created_at DESC");
$notes_stmt->bind_param("i", $user_id);
$notes_stmt->execute();
$notes_result = $notes_stmt->get_result();
$notes = $notes_result->fetch_all(MYSQLI_ASSOC);
$notes_stmt->close();

// Fetch user's groups (for possible future Group Notes upload/filter)
$gstmt = $conn->prepare("SELECT g.id, g.group_name FROM groups g JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id = ?");
$gstmt->bind_param("i", $user_id);
$gstmt->execute();
$userGroups = $gstmt->get_result()->fetch_all(MYSQLI_ASSOC);
$gstmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Notes | StudyCollabo</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<style>
:root{--primary:#1a73e8;--surface:#fff;--bg:#f8f9fa;--text:#202124}
body{font-family:Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; background:var(--bg); color:var(--text); margin:0; overflow-x:hidden;}
main{margin-left:250px;padding:90px 28px 40px;transition:margin-left .3s}
@media(max-width:768px){main{margin-left:0!important;padding-top:80px}}
/* Notes styles */
.notes-header { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; }
.notes-tabs .nav-link { border-radius:10px; font-weight:600; }
.notes-tabs .nav-link.active { background:var(--primary); color:#fff; }
.note-card { background:var(--surface); border:1px solid #eee; border-radius:10px; padding:14px; }
.note-preview { color:#444; margin-top:8px; }
.small-muted{font-size:.85rem;color:#6b7280}
#quillEditor { height:260px; }
.modal-lg { max-width:900px; }
.search-row .form-control, .search-row .form-select { min-height:44px; }
.shadow-1{
    box-shadow: rgba(0, 0, 0, 0.1) 0px 10px 15px -3px, rgba(0, 0, 0, 0.05) 0px 4px 6px -2px;
}
</style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main>
    <?php include '../includes/navbar.php'; ?>

    <div class="notes-header">
        <div>
            <h4 class="m-0">📓 Notes</h4>
            <div class="small-muted">Create personal notes and quickly access them here.</div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-primary" id="openNewNoteBtn"><i class="bi bi-plus-circle me-1"></i> New Note</button>
        </div>
    </div>

    <!-- Filters -->
    <div class="row g-2 mb-3 search-row">
        <div class="col-md-5">
            <input id="noteSearch" class="form-control" placeholder="Search title or content...">
        </div>
        <div class="col-md-3">
            <select id="courseFilter" class="form-select">
                <option value="">All Courses</option>
                <?php if(!empty($user_course = $_SESSION['course'] ?? '')): ?>
                    <option value="<?= htmlspecialchars($user_course) ?>"><?= htmlspecialchars($user_course) ?></option>
                <?php endif; ?>
                <!-- You can populate more course options dynamically if you have a table -->
            </select>
        </div>
        <div class="col-md-4 text-end">
            <small class="text-muted">Showing <span id="notesCount"><?= count($notes) ?></span> notes</small>
        </div>
    </div>

    <!-- Notes grid -->
    <div id="notesContainer" class="row g-3">
        <?php if (count($notes) === 0): ?>
            <div class="col-12 text-center text-muted py-4">No notes yet. Click <strong>New Note</strong> to create one.</div>
        <?php else: ?>
            <?php foreach ($notes as $n): ?>
                <div class="col-md-4">
                    <div class="note-card shadow-1" data-course="<?= htmlspecialchars($n['course']) ?>" data-id="<?= intval($n['id']) ?>">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                            <div style="font-weight:600;font-size:1.02rem;"><?= htmlspecialchars($n['title']) ?: 'Untitled' ?></div>
                            <small class="text-muted"><?= date("M d, Y", strtotime($n['created_at'])) ?></small>
                        </div>

                        <div class="small-muted mt-1">
                            <?= $n['course'] ? htmlspecialchars($n['course']) : '' ?>
                            <?php if ($n['course']): ?> • <?php endif; ?>
                            <?= "By you" ?>
                        </div>

                        <div class="note-preview">
                            <?php
                                $plain = strip_tags($n['content']);
                                if (mb_strlen($plain) > 220) echo nl2br(htmlspecialchars(mb_substr($plain,0,220))) . '...';
                                else echo nl2br(htmlspecialchars($plain));
                            ?>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary openNoteBtn" data-id="<?= intval($n['id']) ?>">Open</button>
                            <button class="btn btn-sm btn-outline-secondary editNoteBtn" data-id="<?= intval($n['id']) ?>">Edit</button>
                            <button class="btn btn-sm btn-danger ms-auto deleteNoteBtn" data-id="<?= intval($n['id']) ?>">Delete</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</main>

<!-- New/Edit Note Modal -->
<div class="modal fade" id="noteModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="noteForm">
      <input type="hidden" name="note_id" id="noteId" value="0">
      <div class="modal-header">
        <h5 class="modal-title" id="noteModalTitle">New Note</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="noteAlert"></div>

        <div class="mb-3">
            <label class="form-label">Title</label>
            <input name="title" id="noteTitle" class="form-control" placeholder="Note title">
        </div>

        <div class="mb-3 row">
            <div class="col-md-6">
                <label class="form-label">Course</label>
                <input name="course" id="noteCourse" class="form-control" placeholder="e.g. CS101" value="<?= htmlspecialchars($_SESSION['course'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Share with group (optional)</label>
                <select name="group_id" id="noteGroup" class="form-select">
                    <option value="0">Private</option>
                    <?php foreach($userGroups as $g): ?>
                        <option value="<?= intval($g['id']) ?>"><?= htmlspecialchars($g['group_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <label class="form-label">Content</label>
        <div id="quillEditor"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button id="saveNoteBtn" type="button" class="btn btn-primary">Save</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- View Note Modal (read-only) -->
<div class="modal fade" id="viewNoteModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewNoteTitle">Note</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="viewNoteMeta" class="small-muted mb-2"></div>
        <div id="viewNoteContent"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<script>
(() => {
    // Quill editor instance for modal
    const quill = new Quill('#quillEditor', {
        theme: 'snow',
        placeholder: 'Write your note...',
        modules: {
            toolbar: [
                ['bold','italic','underline'],
                [{ header: [1,2,3,false] }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'code-block']
            ]
        }
    });

    const bsNoteModal = new bootstrap.Modal(document.getElementById('noteModal'));
    const bsViewModal = new bootstrap.Modal(document.getElementById('viewNoteModal'));

    // Elements
    const openNewNoteBtn = document.getElementById('openNewNoteBtn');
    const saveNoteBtn = document.getElementById('saveNoteBtn');
    const noteForm = document.getElementById('noteForm');
    const noteAlert = document.getElementById('noteAlert');

    const noteIdEl = document.getElementById('noteId');
    const noteTitleEl = document.getElementById('noteTitle');
    const noteCourseEl = document.getElementById('noteCourse');
    const noteGroupEl = document.getElementById('noteGroup');

    const notesContainer = document.getElementById('notesContainer');
    const noteSearch = document.getElementById('noteSearch');
    const courseFilter = document.getElementById('courseFilter');
    const notesCount = document.getElementById('notesCount');

    // Open new note modal
    openNewNoteBtn.addEventListener('click', () => {
        noteForm.reset();
        quill.setContents([{ insert: '\n' }]);
        noteIdEl.value = 0;
        document.getElementById('noteModalTitle').textContent = 'New Note';
        noteAlert.innerHTML = '';
        bsNoteModal.show();
    });

    // Delegate open/edit/delete/view actions
    document.addEventListener('click', (e) => {
        const openBtn = e.target.closest('.openNoteBtn');
        const editBtn = e.target.closest('.editNoteBtn');
        const delBtn = e.target.closest('.deleteNoteBtn');

        if (openBtn) {
            const id = openBtn.getAttribute('data-id');
            openViewNote(id);
        } else if (editBtn) {
            const id = editBtn.getAttribute('data-id');
            openEditNote(id);
        } else if (delBtn) {
            const id = delBtn.getAttribute('data-id');
            deleteNoteConfirm(id);
        }
    });

    // Open note in read-only modal (AJAX fetch)
    function openViewNote(id) {
        fetch('get_note.php?id=' + encodeURIComponent(id))
        .then(r => r.json())
        .then(json => {
            if (json.status === 'success') {
                document.getElementById('viewNoteTitle').textContent = json.note.title || 'Untitled';
                document.getElementById('viewNoteMeta').textContent = (json.note.course ? json.note.course + ' • ' : '') + 'Created: ' + json.note.created_at;
                document.getElementById('viewNoteContent').innerHTML = json.note.content || '';
                bsViewModal.show();
            } else {
                alert(json.message || 'Failed to load note.');
            }
        }).catch(err => { console.error(err); alert('Network error'); });
    }

    // Open edit modal - prefill values
    function openEditNote(id) {
        fetch('get_note.php?id=' + encodeURIComponent(id))
        .then(r => r.json())
        .then(json => {
            if (json.status === 'success') {
                noteIdEl.value = json.note.id;
                noteTitleEl.value = json.note.title || '';
                noteCourseEl.value = json.note.course || '';
                noteGroupEl.value = json.note.group_id || 0;
                quill.root.innerHTML = json.note.content || '';
                document.getElementById('noteModalTitle').textContent = 'Edit Note';
                noteAlert.innerHTML = '';
                bsNoteModal.show();
            } else {
                alert(json.message || 'Failed to load note for editing.');
            }
        }).catch(err => { console.error(err); alert('Network error'); });
    }

    // Delete with confirmation
    function deleteNoteConfirm(id) {
        if (!confirm('Delete this note?')) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);

        fetch('save_note.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            if (json.status === 'success') {
                // remove card
                const card = document.querySelector('.note-card[data-id="'+id+'"]');
                if (card) card.closest('.col-md-4').remove();
                updateCount();
            } else {
                alert(json.message || 'Delete failed');
            }
        }).catch(err => { console.error(err); alert('Network error'); });
    }

    // Save (create or update) note - uses external save_note.php
    saveNoteBtn.addEventListener('click', () => {
        const title = noteTitleEl.value.trim();
        const course = noteCourseEl.value.trim();
        const content = quill.root.innerHTML;

        if (!title) {
            noteAlert.innerHTML = '<div class="alert alert-warning">Please enter a title.</div>';
            return;
        }
        if (!course) {
            noteAlert.innerHTML = '<div class="alert alert-warning">Please enter a course.</div>';
            return;
        }
        if (content === '<p><br></p>' || content.trim() === '') {
            noteAlert.innerHTML = '<div class="alert alert-warning">Please add note content.</div>';
            return;
        }

        noteAlert.innerHTML = '';
        const fd = new FormData();
        fd.append('title', title);
        fd.append('course', course);
        fd.append('content', content);
        fd.append('note_id', noteIdEl.value || 0);
        // action optional - your save_note.php may inspect note_id to decide create/update
        // send to existing external save_note.php
        fetch('save_note.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            if (json.status === 'success') {
                // simple approach: reload to show updated list
                location.reload();
            } else {
                noteAlert.innerHTML = '<div class="alert alert-danger">'+(json.message || 'Save failed')+'</div>';
            }
        }).catch(err => {
            console.error(err);
            noteAlert.innerHTML = '<div class="alert alert-danger">Network error while saving.</div>';
        });
    });

    // Search & filter (client-side)
    function filterNotes() {
        const term = (noteSearch.value || '').toLowerCase();
        const course = (courseFilter.value || '').toLowerCase();
        let shown = 0;
        document.querySelectorAll('#notesContainer .note-card').forEach(card => {
            const title = (card.querySelector('[style*="font-weight:600"]')?.textContent || '').toLowerCase();
            const preview = (card.querySelector('.note-preview')?.textContent || '').toLowerCase();
            const c = (card.getAttribute('data-course') || '').toLowerCase();

            const matchesText = !term || title.includes(term) || preview.includes(term);
            const matchesCourse = !course || c === course;

            const parentCol = card.closest('.col-md-4');
            if (matchesText && matchesCourse) {
                if (parentCol) parentCol.style.display = '';
                shown++;
            } else {
                if (parentCol) parentCol.style.display = 'none';
            }
        });
        if (notesCount) notesCount.textContent = shown;
    }

    noteSearch.addEventListener('input', filterNotes);
    courseFilter.addEventListener('change', filterNotes);

    // update initial count
    function updateCount() {
        const visible = document.querySelectorAll('#notesContainer .col-md-4:not([style*="display: none"])').length;
        notesCount.textContent = visible || 0;
    }
    updateCount();

    // Ensure sidebar toggle works (keep consistent ID)
    const toggleBtn = document.getElementById('toggleSidebar');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            const main = document.querySelector('main');
            if (sidebar) sidebar.classList.toggle('collapsed');
            if (main) main.classList.toggle('collapsed');
        });
    }
})();
</script>
</body>
</html>