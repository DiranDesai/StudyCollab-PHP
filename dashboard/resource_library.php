<?php
// resource_library.php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id     = intval($_SESSION['user_id']);
$user_name   = $_SESSION['name'] ?? 'Student';
$user_email  = $_SESSION['email'] ?? 'student@example.com';
$user_course = $_SESSION['course'] ?? '';
$first_name  = explode(' ', trim($user_name))[0];
$profile_photo = $_SESSION['profile_photo'] ?? null;

// Upload configuration
$UPLOAD_DIR     = __DIR__ . '/../uploads/resources/';           // server path
$WEB_UPLOAD_DIR = '/studyCollab/uploads/resources/';           // path used in links (adjust for your app)
$MAX_FILE_SIZE  = 25 * 1024 * 1024;                            // 25 MB per file
$ALLOWED_MIMES  = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/zip',
    'text/plain',
    'image/png',
    'image/jpeg'
];

if (!is_dir($UPLOAD_DIR)) @mkdir($UPLOAD_DIR, 0755, true);

// -------------------- AJAX handlers --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'];

    // ---------- UPLOAD (supports multiple files) ----------
    if ($action === 'upload') {
        // Note: form fields: title (optional per file), description (optional), course, group_id
        // We'll accept one course + one group for all uploaded files in this batch.
        $course = trim($_POST['course'] ?? '');
        $group_id = intval($_POST['group_id'] ?? 0);
        $description_common = trim($_POST['description'] ?? '');

        // Files may be in 'files[]' (multiple) or single 'file'
        $files = [];
        if (!empty($_FILES['files']) && is_array($_FILES['files']['name'])) {
            // normalize multiple files
            foreach ($_FILES['files']['name'] as $i => $name) {
                $files[] = [
                    'name' => $_FILES['files']['name'][$i],
                    'type' => $_FILES['files']['type'][$i],
                    'tmp_name' => $_FILES['files']['tmp_name'][$i],
                    'error' => $_FILES['files']['error'][$i],
                    'size' => $_FILES['files']['size'][$i],
                ];
            }
        } elseif (!empty($_FILES['file'])) {
            $files[] = $_FILES['file'];
        }

        if (count($files) === 0) {
            echo json_encode(['success' => false, 'message' => 'No files uploaded.']);
            exit;
        }

        $results = [];
        foreach ($files as $fidx => $file) {
            // validate
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $results[] = ['ok' => false, 'file' => $file['name'], 'message' => 'Upload error code: '.$file['error']];
                continue;
            }
            if ($file['size'] > $MAX_FILE_SIZE) {
                $results[] = ['ok' => false, 'file' => $file['name'], 'message' => 'File too large'];
                continue;
            }

            // mime check (best-effort)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, $ALLOWED_MIMES)) {
                $results[] = ['ok' => false, 'file' => $file['name'], 'message' => 'File type not allowed ('.$mime.')'];
                continue;
            }

            // safe filename
            $orig = basename($file['name']);
            $orig = preg_replace('/[^A-Za-z0-9\-\._ ]/', '_', $orig);
            $ext = pathinfo($orig, PATHINFO_EXTENSION);
            $base = substr(str_replace(' ', '_', pathinfo($orig, PATHINFO_FILENAME)), 0, 80);
            try {
                $unique = time() . '_' . bin2hex(random_bytes(6));
            } catch (Exception $e) {
                $unique = time() . '_' . bin2hex(openssl_random_pseudo_bytes(6));
            }
            $newName = $base . '_' . $unique . ($ext ? '.' . $ext : '');
            $destination = $UPLOAD_DIR . $newName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $results[] = ['ok' => false, 'file' => $file['name'], 'message' => 'Failed to move file'];
                continue;
            }

            $file_web_path = $WEB_UPLOAD_DIR . $newName;
            $course_db = $course ?: '';
            $group_db = $group_id ?: 0;
            // description and title: allow per-file override if provided via POST arrays (title[] description[]), else use common
            $title = trim($_POST['title'][$fidx] ?? $_POST['title_common'] ?? $base);
            $description = trim($_POST['description'][$fidx] ?? $description_common ?? '');

            // insert into DB (bind variables explicitly)
            $sql = "INSERT INTO resources (user_id, course, group_id, title, description, file_path, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                // cleanup
                @unlink($destination);
                $results[] = ['ok' => false, 'file' => $file['name'], 'message' => 'DB prepare error'];
                continue;
            }
            // bind variables explicitly
            $uid = $user_id;
            $course_bind = $course_db;
            $group_bind = $group_db; // as int
            $title_bind = $title;
            $desc_bind = $description;
            $path_bind = $file_web_path;

            // Note: using "isssss" to keep consistent with prior code (group_id saved as string/number)
            $stmt->bind_param("isssss", $uid, $course_bind, $group_bind, $title_bind, $desc_bind, $path_bind);
            $ok = $stmt->execute();
            $stmt->close();

            if (!$ok) {
                @unlink($destination);
                $results[] = ['ok' => false, 'file' => $file['name'], 'message' => 'DB insert failed'];
                continue;
            }

            $results[] = ['ok' => true, 'file' => $file['name'], 'message' => 'Uploaded'];
        } // end foreach files

        // return results summary
        echo json_encode(['success' => true, 'results' => $results]);
        exit;
    }

    // ---------- DELETE ----------
    if ($action === 'delete') {
        $rid = intval($_POST['id'] ?? 0);
        if ($rid <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }

        $stmt = $conn->prepare("SELECT user_id, file_path FROM resources WHERE id = ?");
        $stmt->bind_param("i", $rid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) { echo json_encode(['success'=>false,'message'=>'Not found']); exit; }
        if (intval($row['user_id']) !== $user_id) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }

        $stmt2 = $conn->prepare("DELETE FROM resources WHERE id = ?");
        $stmt2->bind_param("i", $rid);
        $ok = $stmt2->execute();
        $stmt2->close();

        if ($ok) {
            $disk = __DIR__ . '/../' . ltrim($row['file_path'], '/');
            if (file_exists($disk)) @unlink($disk);
            echo json_encode(['success'=>true,'message'=>'Deleted']);
        } else {
            echo json_encode(['success'=>false,'message'=>'Delete failed']);
        }
        exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action']);
    exit;
}

// -------------------- Fetch resources for display --------------------
// get groups for user
$groupIds = [];
$gm = $conn->prepare("SELECT group_id FROM group_members WHERE user_id = ?");
$gm->bind_param("i", $user_id);
$gm->execute();
$res_gm = $gm->get_result();
while ($g = $res_gm->fetch_assoc()) $groupIds[] = intval($g['group_id']);
$gm->close();

$groupPlaceholders = count($groupIds) ? implode(',', $groupIds) : '0';

$sql = "SELECT r.*, u.fullname FROM resources r JOIN users u ON r.user_id=u.id
        WHERE ((r.course<>'' AND r.course=?) OR r.group_id IN ($groupPlaceholders) OR r.user_id=?)
        ORDER BY r.uploaded_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $user_course, $user_id);
$stmt->execute();
$resAll = $stmt->get_result();
$resources = $resAll->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$courses = [];
$cq = $conn->query("SELECT DISTINCT course FROM resources WHERE course <> ''");
while ($rw = $cq->fetch_assoc()) $courses[] = $rw['course'];

$gstmt = $conn->prepare("SELECT g.id, g.group_name FROM groups g JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id = ?");
$gstmt->bind_param("i", $user_id);
$gstmt->execute();
$userGroups = $gstmt->get_result()->fetch_all(MYSQLI_ASSOC);
$gstmt->close();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Resource Library | StudyCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root{--primary:#1a73e8;--surface:#fff;--bg:#f8f9fa;--text:#202124}
body{font-family:Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; background:var(--bg); color:var(--text); margin:0; overflow-x:hidden;}
main{margin-left:250px;padding:90px 28px 40px;transition:margin-left .3s}
@media(max-width:768px){main{margin-left:0!important;padding-top:80px}}
.upload-drop { border:2px dashed #dfe7fd; background:#fff; padding:18px; border-radius:10px; text-align:center; cursor:pointer; }
.upload-drop.dragover { background:#eef4ff; border-color:var(--primary); }
.file-list { list-style:none; padding:0; margin:0; }
.file-item { background:#fff; border:1px solid #eee; padding:10px; border-radius:8px; display:flex; gap:10px; align-items:center; }
.file-thumb { width:58px; height:48px; border-radius:6px; object-fit:cover; background:#f5f5f7; display:inline-block; }
.progress { height:10px; }
.resource-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:12px; margin-top:12px; }
.resource-card { background:#fff;border:1px solid #eee;padding:14px;border-radius:10px;display:flex;flex-direction:column;justify-content:space-between; min-height:150px;}
.small-muted{font-size:.85rem;color:#6b7280}
</style>
</head>
<body>
<?php include '../includes/sidebar.php' ?>

<main>
    <?php include '../includes/navbar.php' ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="m-0">Resource Library</h4>
        <div>
            <button class="btn btn-outline-primary me-2" id="openUploadBtn"><i class="bi bi-upload me-1"></i> Upload Resource</button>
        </div>
    </div>

    <!-- Filters -->
    <div class="row g-2 align-items-center mb-3">
        <div class="col-md-4">
            <input id="resourceSearch" class="form-control" placeholder="Search title or description...">
        </div>
        <div class="col-md-3">
            <select id="resourceCourseFilter" class="form-select">
                <option value="">All Courses</option>
                <?php if ($user_course): ?>
                    <option value="<?= htmlspecialchars($user_course) ?>" selected><?= htmlspecialchars($user_course) ?></option>
                <?php endif; ?>
                <?php foreach ($courses as $c): if ($c === $user_course) continue; ?>
                    <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select id="resourceGroupFilter" class="form-select">
                <option value="">All Groups</option>
                <?php foreach ($userGroups as $g): ?>
                    <option value="<?= intval($g['id']) ?>"><?= htmlspecialchars($g['group_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 text-end">
            <small class="text-muted">Showing <span id="resourceCount"><?= count($resources) ?></span></small>
        </div>
    </div>

    <!-- Resource cards -->
    <div id="resourceContainer" class="resource-cards">
        <?php if (count($resources) === 0): ?>
            <div class="text-center text-muted">No resources found.</div>
        <?php else: foreach ($resources as $r): ?>
            <div class="resource-card" data-course="<?= htmlspecialchars($r['course']) ?>" data-group="<?= intval($r['group_id']) ?>">
                <div>
                    <div style="font-weight:600;font-size:1.05rem"><?= htmlspecialchars($r['title']) ?></div>
                    <div class="mt-2"><?= nl2br(htmlspecialchars(substr($r['description'],0,220))) ?><?= strlen($r['description'])>220 ? '...' : '' ?></div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="small-muted">
                        <?= ($r['course'] ? htmlspecialchars($r['course']) . ' • ' : '') ?>
                        <?= ($r['group_id'] ? 'Group: ' . intval($r['group_id']) . ' • ' : '') ?>
                        Uploaded by <?= htmlspecialchars($r['fullname']) ?> • <?= date("M d, Y", strtotime($r['uploaded_at'])) ?>
                    </div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($r['file_path']) ?>" target="_blank" download><i class="bi bi-download"></i></a>
                        <?php if (intval($r['user_id']) === $user_id): ?>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="<?= $r['id'] ?>"><i class="bi bi-trash"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</main>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Upload Resources (multiple supported)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
          <div id="uploadAlert"></div>

          <div class="mb-3">
              <label class="form-label">Course (optional)</label>
              <input type="text" id="inputCourse" class="form-control" placeholder="e.g. CS101" value="<?= htmlspecialchars($user_course) ?>">
          </div>

          <div class="mb-3">
              <label class="form-label">Group (optional)</label>
              <select id="inputGroup" class="form-select">
                  <option value="0">-- Select Group --</option>
                  <?php foreach ($userGroups as $g): ?>
                    <option value="<?= intval($g['id']) ?>"><?= htmlspecialchars($g['group_name']) ?></option>
                  <?php endforeach; ?>
              </select>
          </div>

          <div class="mb-3">
              <label class="form-label">Description (optional, applies to all)</label>
              <textarea id="inputDescription" class="form-control" rows="2"></textarea>
          </div>

          <div class="mb-3">
              <div id="dropArea" class="upload-drop">
                  <p class="mb-0"><i class="bi bi-cloud-upload" style="font-size:28px;color:var(--primary)"></i></p>
                  <p class="mb-0">Drag &amp; drop files here, or <button id="pickFilesBtn" type="button" class="btn btn-link p-0">browse</button></p>
                  <small class="text-muted">Allowed: pdf, doc/docx, ppt/pptx, zip, txt, png, jpg. Max <?= $MAX_FILE_SIZE/1024/1024 ?> MB each.</small>
                  <input type="file" id="fileInput" name="files[]" multiple style="display:none">
              </div>
          </div>

          <div class="mb-3">
              <ul id="fileList" class="file-list"></ul>
          </div>

          <div class="mb-3">
              <div class="progress" style="height:12px; display:none" id="uploadProgressWrap">
                  <div id="uploadProgress" class="progress-bar" role="progressbar" style="width:0%"></div>
              </div>
              <small id="uploadStatus" class="text-muted"></small>
          </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" id="cancelUploadBtn">Close</button>
        <button class="btn btn-primary" id="startUploadBtn"><i class="bi bi-cloud-arrow-up me-1"></i> Start Upload</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    // Elements
    const openUploadBtn = document.getElementById('openUploadBtn');
    const uploadModalEl = document.getElementById('uploadModal');
    const fileInput = document.getElementById('fileInput');
    const pickFilesBtn = document.getElementById('pickFilesBtn');
    const dropArea = document.getElementById('dropArea');
    const fileList = document.getElementById('fileList');
    const startUploadBtn = document.getElementById('startUploadBtn');
    const uploadProgressWrap = document.getElementById('uploadProgressWrap');
    const uploadProgress = document.getElementById('uploadProgress');
    const uploadStatus = document.getElementById('uploadStatus');
    const uploadAlert = document.getElementById('uploadAlert');
    const inputCourse = document.getElementById('inputCourse');
    const inputGroup = document.getElementById('inputGroup');
    const inputDescription = document.getElementById('inputDescription');
    const bsModal = new bootstrap.Modal(uploadModalEl);
    let filesState = []; // {file, id, removed}

    openUploadBtn.addEventListener('click', () => {
        uploadAlert.innerHTML = '';
        fileList.innerHTML = '';
        filesState = [];
        uploadProgressWrap.style.display = 'none';
        uploadProgress.style.width = '0%';
        uploadStatus.textContent = '';
        bsModal.show();
    });

    pickFilesBtn.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

    // drag & drop
    ['dragenter','dragover'].forEach(ev => {
        dropArea.addEventListener(ev, (e) => {
            e.preventDefault(); e.stopPropagation();
            dropArea.classList.add('dragover');
        });
    });
    ['dragleave','drop'].forEach(ev => {
        dropArea.addEventListener(ev, (e) => {
            e.preventDefault(); e.stopPropagation();
            dropArea.classList.remove('dragover');
        });
    });
    dropArea.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        if (dt && dt.files) handleFiles(dt.files);
    });

    function humanFileSize(bytes) {
        const thresh = 1024;
        if (Math.abs(bytes) < thresh) return bytes + ' B';
        const units = ['KB','MB','GB','TB','PB','EB','ZB','YB'];
        let u = -1;
        do { bytes /= thresh; ++u; } while (Math.abs(bytes) >= thresh && u < units.length - 1);
        return bytes.toFixed(1)+' '+units[u];
    }

    function handleFiles(list) {
        Array.from(list).forEach(file => {
            // avoid duplicates by name+size
            const exists = filesState.some(f => f.file.name === file.name && f.file.size === file.size);
            if (exists) return;
            const id = Date.now().toString(36) + Math.random().toString(36).slice(2,8);
            const item = {file, id, removed:false};
            filesState.push(item);
            renderFileItem(item);
        });
    }

    function renderFileItem(item) {
        const li = document.createElement('li');
        li.className = 'file-item mb-2';
        li.dataset.id = item.id;

        const thumb = document.createElement('div');
        thumb.className = 'file-thumb';
        // if image show preview
        if (item.file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.className = 'file-thumb';
            img.style.width='58px'; img.style.height='48px';
            const reader = new FileReader();
            reader.onload = (e) => img.src = e.target.result;
            reader.readAsDataURL(item.file);
            thumb.appendChild(img);
        } else {
            thumb.innerHTML = '<i class="bi bi-file-earmark-fill" style="font-size:22px"></i>';
        }

        const meta = document.createElement('div');
        meta.style.flex='1';
        meta.innerHTML = '<div style="font-weight:600">'+escapeHtml(item.file.name)+'</div><div class="small-muted">'+humanFileSize(item.file.size)+'</div>';

        const actions = document.createElement('div');
        actions.innerHTML = '<button type="button" class="btn btn-sm btn-outline-danger remove-file-btn"><i class="bi bi-x-lg"></i></button>';

        li.appendChild(thumb);
        li.appendChild(meta);
        li.appendChild(actions);

        fileList.appendChild(li);

        // remove handler
        actions.querySelector('.remove-file-btn').addEventListener('click', () => {
            item.removed = true;
            filesState = filesState.filter(f => f.id !== item.id);
            li.remove();
        });
    }

    function escapeHtml(s){ return s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

    // Start upload
    let currentXhr = null;
    startUploadBtn.addEventListener('click', () => {
        if (filesState.length === 0) { uploadAlert.innerHTML = '<div class="alert alert-warning">No files selected</div>'; return; }

        // build FormData
        const fd = new FormData();
        fd.append('action','upload');
        fd.append('course', inputCourse.value || '');
        fd.append('group_id', inputGroup.value || 0);
        fd.append('description', inputDescription.value || '');
        // append files[] and optional per-file titles/descriptions if you extend
        filesState.forEach(f => fd.append('files[]', f.file));

        // create xhr
        const xhr = new XMLHttpRequest();
        currentXhr = xhr;
        xhr.open('POST', window.location.pathname, true);

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                uploadProgressWrap.style.display = 'block';
                const percent = Math.round((e.loaded / e.total) * 100);
                uploadProgress.style.width = percent + '%';
                uploadProgress.setAttribute('aria-valuenow', percent);
                uploadStatus.textContent = percent + '%';
            }
        });

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                currentXhr = null;
                if (xhr.status === 200) {
                    try {
                        const json = JSON.parse(xhr.responseText);
                        if (json.success) {
                            // show per-file results if provided
                            let html = '<div class="alert alert-success">Upload completed.</div>';
                            if (Array.isArray(json.results)) {
                                html = '<div class="alert alert-info"><strong>Results:</strong><ul>';
                                json.results.forEach(r => {
                                    html += '<li>' + escapeHtml(r.file) + ': ' + escapeHtml(r.message) + '</li>';
                                });
                                html += '</ul></div>';
                            }
                            uploadAlert.innerHTML = html;
                            // refresh page after short delay
                            setTimeout(()=> location.reload(), 900);
                        } else {
                            uploadAlert.innerHTML = '<div class="alert alert-danger">' + escapeHtml(json.message || 'Upload failed') + '</div>';
                        }
                    } catch (err) {
                        uploadAlert.innerHTML = '<div class="alert alert-danger">Unexpected server response.</div>';
                        console.error(err, xhr.responseText);
                    }
                } else {
                    uploadAlert.innerHTML = '<div class="alert alert-danger">Upload failed (status '+xhr.status+').</div>';
                }
            }
        };

        xhr.send(fd);
    });

    // Cancel upload on modal close
    document.getElementById('cancelUploadBtn').addEventListener('click', () => {
        if (currentXhr) { currentXhr.abort(); currentXhr = null; }
        bsModal.hide();
    });

    // Filters & search for showing cards
    const searchEl = document.getElementById('resourceSearch');
    const courseEl = document.getElementById('resourceCourseFilter');
    const groupEl = document.getElementById('resourceGroupFilter');
    const container = document.getElementById('resourceContainer');
    const countEl = document.getElementById('resourceCount');

    function filterResources(){
        const term = (searchEl.value || '').toLowerCase();
        const course = (courseEl.value || '');
        const group = (groupEl.value || '');
        let shown = 0;
        Array.from(container.children).forEach(card => {
            if (!card.classList || !card.classList.contains('resource-card')) return;
            const title = (card.querySelector('[style*="font-weight:600"]')?.textContent || '').toLowerCase();
            const desc = (card.querySelector('div:nth-child(2)')?.textContent || '').toLowerCase();
            const c = (card.getAttribute('data-course') || '');
            const g = (card.getAttribute('data-group') || '');
            const matchesText = (!term) || title.includes(term) || desc.includes(term);
            const matchesCourse = (!course) || (c === course);
            const matchesGroup = (!group) || (g === group);
            if (matchesText && matchesCourse && matchesGroup) { card.style.display = 'block'; shown++; } else { card.style.display = 'none'; }
        });
        if (countEl) countEl.textContent = shown;
    }

    searchEl.addEventListener('input', filterResources);
    courseEl.addEventListener('change', filterResources);
    groupEl.addEventListener('change', filterResources);

    // Delete via AJAX delegation
    document.addEventListener('click', function(e){
        const btn = e.target.closest('.delete-btn');
        if (!btn) return;
        if (!confirm('Delete resource?')) return;
        const id = btn.getAttribute('data-id');
        fetch(window.location.pathname, {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'action=delete&id='+encodeURIComponent(id)
        })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                const card = btn.closest('.resource-card'); if (card) card.remove();
                filterResources();
            } else {
                alert(json.message || 'Delete failed');
            }
        }).catch(err => { console.error(err); alert('Delete error'); });
    });

})();
</script>
</body>
</html>