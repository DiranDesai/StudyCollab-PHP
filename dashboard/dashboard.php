<?php
session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$user_name  = $_SESSION['name'] ?? 'Student';
$user_email = $_SESSION['email'] ?? 'student@example.com';
$first_name = explode(' ', trim($user_name))[0];

// Profile photo (null if not uploaded)
$profile_photo = $_SESSION['profile_photo'] ?? null;

// Fetch data from DB
$total_tasks     = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
$completed_tasks = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE user_id = $user_id AND status='completed'")->fetch_assoc()['total'] ?? 0;
$group_tasks     = $conn->query("SELECT COUNT(*) AS total FROM group_tasks WHERE leader_id = $user_id OR group_id IN (SELECT group_id FROM group_members WHERE user_id = $user_id)")->fetch_assoc()['total'] ?? 0;
$progress        = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;
$recent_activity = $conn->query("SELECT title, created_at FROM group_tasks ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | StudyCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    
/* Root & Body */
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


.logo1{
    transform: scale(3.4);
    margin-left: 40px;
}

/* Topbar */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    height: 64px;
    background: var(--surface);
    padding: 0 20px;
    border-bottom: 1px solid #ddd;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 99;
}

/* Topbar elements */
.top-left { display:flex; align-items:center; gap:10px; }
.top-left img { height:32px; }
.search-box { position:relative; width:320px; }
.search-box input { width:100%; padding:8px 35px; border-radius:25px; border:1px solid #ddd; background:#f1f3f4; }
.search-box i { position:absolute; top:8px; left:12px; color:#888; }

/* Profile */
.profile-btn { border:none; background:transparent; display:flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:50%; font-weight:600; font-size:14px; color:#fff; background:#1a73e8; cursor:pointer; position:relative; }
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

/* Cards */
.dashboard-cards { display:flex; flex-wrap:wrap; gap:20px; }
.card-stat { flex:1; min-width:220px; background:var(--surface); border:1px solid #ddd; border-radius:12px; padding:20px; text-align:center; transition:transform .2s, box-shadow .2s; }
.card-stat:hover { transform:translateY(-3px); box-shadow:0 4px 15px rgba(0,0,0,0.08); }
.card-stat h3 { font-size:2rem; margin:10px 0 5px; color: var(--primary); }
.card-stat p { margin:0; font-weight:500; color:#555; }
.progress { height:5px; border-radius:10px; background:#e0e0e0; margin-top:10px; }
.progress-bar { background: var(--primary); }

/* Recent Activity */
.recent-activity { margin-top:40px; }
.recent-activity .list-group-item { border:none; border-bottom:1px solid #eee; padding:12px 16px; }
.recent-activity .list-group-item:hover { background:#fafafa; }

/* Responsive */
@media(max-width:768px){ main{margin-left:0 !important; padding-top:80px;} .sidebar{display:none;} .topbar{padding-left:20px !important;} }

</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="logo"><i class="bi bi-journal-text"></i> <span>StudyCollabo</span></div>
    <ul>
        <li><a href="#" class="active"><i class="bi bi-grid"></i> <span>Dashboard</span></a></li>
        <li><a href="tasks.php"><i class="bi bi-person-check"></i> <span>My Tasks</span></a></li>
        <li><a href="group_tasks.php"><i class="bi bi-people"></i> <span>Group Tasks</span></a></li>
        <li><a href="calendar.php"><i class="bi bi-calendar3"></i> <span>Calendar</span></a></li>
        <li><a href="discussions.php"><i class="bi bi-chat-dots"></i> <span>Discussions</span></a></li>
    </ul>
</div>

<!-- Main -->
<main id="main">
    <div class="topbar" id="topbar">
        <div class="top-left">
            <button class="btn btn-light" id="toggleSidebar"><i class="bi bi-list"></i></button>
            <img src="../assets/img/SClogo.png" alt="StudyCollabo Logo" style="height:32px; width:auto;" class="logo1">
        </div>

        <div class="search-box mx-auto">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search tasks, groups..." id="searchBox">
            <ul id="searchResults" class="list-unstyled" style="position:absolute; top:110%; left:0; width:100%; background:#fff; border:1px solid #ddd; border-radius:8px; display:none; z-index:10;"></ul>
        </div>

        <div class="d-flex align-items-center gap-2">
            <div class="dropdown">
                <button class="btn btn-primary rounded-pill px-3" data-bs-toggle="dropdown" style="background-color:#1a73e8;">
                    + Create
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="tasks.php">New Task</a></li>
                    <li><a class="dropdown-item" href="group_tasks.php">New Group Task</a></li>
                </ul>
            </div>

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
                        <div style="width:60px;height:60px;border-radius:50%;background:#1a73e8;color:#fff;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:600;margin:0 auto 10px;"><?= strtoupper(substr($first_name,0,1)) ?></div>
                    <?php endif; ?>
                    <h6>Hey, <?= htmlspecialchars($first_name) ?></h6>
                    <small><?= htmlspecialchars($user_email) ?></small>
                </div>
                <a href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a>
                <a href="../auth/logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
            </div>
        </div>
    </div>

    <!-- Dashboard Cards -->
    <div class="dashboard-cards">
        <div class="card-stat">
            <i class="bi bi-person-check-fill fs-3 text-primary"></i>
            <h3><?= $total_tasks ?></h3>
            <p>My Tasks</p>
            <div class="progress"><div class="progress-bar" style="width: <?= $progress ?>%;"></div></div>
        </div>

        <div class="card-stat">
            <i class="bi bi-people-fill fs-3 text-success"></i>
            <h3><?= $group_tasks ?></h3>
            <p>Group Tasks</p>
        </div>

        <div class="card-stat">
            <i class="bi bi-graph-up fs-3 text-info"></i>
            <h3><?= $progress ?>%</h3>
            <p>Progress</p>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity mt-4">
        <h6 class="fw-semibold mb-3">Recent Activity</h6>
        <div class="card border-0 shadow-sm rounded-3">
            <div class="list-group list-group-flush">
                <?php if($recent_activity->num_rows>0): ?>
                    <?php while($a=$recent_activity->fetch_assoc()): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span><?= htmlspecialchars($a['title']) ?></span>
                            <small class="text-muted"><?= date("M d, Y", strtotime($a['created_at'])) ?></small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="list-group-item text-center text-muted py-3">No recent activity</div>
                <?php endif; ?>
            </div>
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

toggleSidebar.addEventListener('click',()=>{sidebar.classList.toggle('collapsed'); document.getElementById('main').classList.toggle('collapsed'); document.getElementById('topbar').classList.toggle('collapsed'); });

// Profile dropdown
profileBtn.addEventListener('click', e=>{ e.stopPropagation(); profileMenu.classList.toggle('active'); });
document.addEventListener('click', e=>{ if(!profileMenu.contains(e.target)) profileMenu.classList.remove('active'); });

// AJAX search
const searchBox = document.getElementById('searchBox');
const searchResults = document.getElementById('searchResults');
searchBox.addEventListener('input',()=>{
    const query = searchBox.value.trim();
    if(query.length<2) return searchResults.style.display='none';
    fetch('search_suggestions.php?q='+encodeURIComponent(query))
        .then(res=>res.json())
        .then(data=>{
            searchResults.innerHTML='';
            if(data.length>0){
                data.forEach(item=>{
                    const li=document.createElement('li');
                    li.textContent=item.title;
                    li.style.padding='8px 12px'; li.style.cursor='pointer';
                    li.onclick=()=>window.location.href='task_view.php?id='+item.id;
                    searchResults.appendChild(li);
                });
                searchResults.style.display='block';
            } else {
                searchResults.innerHTML='<li class="text-muted">No results found</li>';
                searchResults.style.display='block';
            }
        });
});
</script>
</body>
</html>