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

// Fetch resources & notes based on user's course
$user_course = $_SESSION['course'] ?? null;
$resources = $conn->query("SELECT r.*, u.fullname FROM resources r 
    JOIN users u ON r.user_id=u.id 
    WHERE r.course='". $conn->real_escape_string($user_course) ."' OR r.user_id=$user_id 
    ORDER BY uploaded_at DESC");

$notes = $conn->query("SELECT n.*, u.fullname FROM notes n 
    JOIN users u ON n.user_id=u.id 
    WHERE n.course='". $conn->real_escape_string($user_course) ."' OR n.user_id=$user_id 
    ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resource Library | StudyCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root { --primary:#1a73e8; --surface:#fff; --bg:#f8f9fa; --text:#202124; --muted:#5f6368; }
body { font-family:'Google Sans',sans-serif; margin:0; background:var(--bg); color:var(--text); overflow-x:hidden; }

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

/* Main content */
main { margin-left:250px; padding:90px 30px 40px; transition: margin-left 0.3s; }
main.collapsed { margin-left:80px; }

/* Resource Library */
.resource-section { margin-top:20px; }
.resource-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:16px; }
.resource-card { background: var(--surface); border:1px solid #ddd; border-radius:12px; padding:16px; transition:transform 0.2s, box-shadow 0.2s; }
.resource-card:hover { transform:translateY(-2px); box-shadow:0 2px 8px rgba(0,0,0,0.08); }
.resource-title { font-weight:600; font-size:1.1rem; margin-bottom:5px; }
.resource-desc { color:#555; margin-bottom:10px; }
.resource-meta { font-size:0.85rem; color:#888; }

/* Dropdown */
.dropdown-menu-custom { position:absolute; background:#fff; border:1px solid #ddd; border-radius:8px; display:none; min-width:180px; z-index:50; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
.dropdown-menu-custom a { display:block; padding:10px; text-decoration:none; color:#333; }
.dropdown-menu-custom a:hover { background:#f5f5f5; }

@media(max-width:768px){ main{margin-left:0 !important; padding-top:80px;} .sidebar{display:none;} .topbar{padding-left:20px !important;} .resource-cards{grid-template-columns:1fr;} }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="logo"><i class="bi bi-journal-text"></i> <span>StudyCollabo</span></div>
    <ul>
        <li><a href="dashboard.php"><i class="bi bi-grid"></i> <span>Dashboard</span></a></li>
        <li><a href="tasks.php"><i class="bi bi-person-check"></i> <span>My Tasks</span></a></li>
        <li><a href="group_tasks.php"><i class="bi bi-people"></i> <span>Group Tasks</span></a></li>
        <li><a href="calendar.php"><i class="bi bi-calendar3"></i> <span>Calendar</span></a></li>
        <li><a href="discussions.php"><i class="bi bi-chat-dots"></i> <span>Discussions</span></a></li>
        <li><a href="resources.php" class="active"><i class="bi bi-journal-bookmark"></i> <span>Resources</span></a></li>
    </ul>
</div>

<!-- Main -->
<main id="main">
    <div class="topbar" id="topbar">
        <div class="top-left">
            <button class="btn btn-light" id="toggleSidebar"><i class="bi bi-list"></i></button>
            <img src="assets/img/SClogo.png" alt="StudyCollabo Logo" style="height:32px; width:auto;">
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
                        <div style="width:60px;height:60px;border-radius:50%;background:#1a73e8;color:#fff;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:600;margin:0 auto 10px;">
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

    <!-- Resource Library -->
    <div class="resource-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Resource Library</h4>
            <div class="position-relative">
                <button class="btn btn-primary" id="resourceDropdownBtn">+ Add <i class="bi bi-caret-down-fill"></i></button>
                <div class="dropdown-menu-custom" id="resourceDropdownMenu">
                    <a href="upload_resource.php">Upload Resource</a>
                    <a href="add_note.php">Add Note</a>
                </div>
            </div>
        </div>

        <div class="mb-3 d-flex gap-2 flex-wrap">
            <input type="text" id="resourceSearch" class="form-control" placeholder="Search resources/notes...">
            <select id="resourceCourseFilter" class="form-select" style="max-width:200px;">
                <option value="">All Courses</option>
                <?php
                $courses = $conn->query("SELECT DISTINCT course FROM resources UNION SELECT DISTINCT course FROM notes");
                while($c=$courses->fetch_assoc()){
                    $selected = ($c['course']==$user_course) ? 'selected' : '';
                    echo "<option value='".htmlspecialchars($c['course'])."' $selected>".htmlspecialchars($c['course'])."</option>";
                }
                ?>
            </select>
        </div>

        <div class="resource-cards" id="resourceContainer">
            <?php while($r=$resources->fetch_assoc()): ?>
                <div class="resource-card" data-course="<?= htmlspecialchars($r['course']) ?>">
                    <div class="resource-title"><?= htmlspecialchars($r['title']) ?></div>
                    <div class="resource-desc"><?= htmlspecialchars($r['description']) ?></div>
                    <div class="resource-meta">Uploaded by: <?= htmlspecialchars($r['fullname']) ?> | Course: <?= htmlspecialchars($r['course']) ?></div>
                </div>
            <?php endwhile; ?>
            <?php while($n=$notes->fetch_assoc()): ?>
                <div class="resource-card" data-course="<?= htmlspecialchars($n['course']) ?>">
                    <div class="resource-title"><?= htmlspecialchars($n['title']) ?> (Note)</div>
                    <div class="resource-desc"><?= htmlspecialchars($n['content']) ?></div>
                    <div class="resource-meta">Added by: <?= htmlspecialchars($n['fullname']) ?> | Course: <?= htmlspecialchars($n['course']) ?></div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</main>

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

// Resource dropdown
const resourceDropdownBtn = document.getElementById('resourceDropdownBtn');
const resourceDropdownMenu = document.getElementById('resourceDropdownMenu');
resourceDropdownBtn.addEventListener('click', e=>{
    e.stopPropagation();
    resourceDropdownMenu.style.display = resourceDropdownMenu.style.display==='block' ? 'none' : 'block';
});
document.addEventListener('click', e=>{
    if(!resourceDropdownMenu.contains(e.target)) resourceDropdownMenu.style.display='none';
});

// Search & filter
const resourceSearch = document.getElementById('resourceSearch');
const resourceCourseFilter = document.getElementById('resourceCourseFilter');
const resourceContainer = document.getElementById('resourceContainer');

function filterResources(){
    const term = resourceSearch.value.toLowerCase();
    const course = resourceCourseFilter.value;
    Array.from(resourceContainer.children).forEach(card=>{
        const title = card.querySelector('.resource-title').textContent.toLowerCase();
        const desc = card.querySelector('.resource-desc').textContent.toLowerCase();
        const cardCourse = card.getAttribute('data-course');
        card.style.display = 
            (title.includes(term) || desc.includes(term)) &&
            (course === "" || course === cardCourse)
            ? 'block' : 'none';
    });
}

resourceSearch.addEventListener('input', filterResources);
resourceCourseFilter.addEventListener('change', filterResources);
</script>
</body>
</html>