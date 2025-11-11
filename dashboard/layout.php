<?php
// layout.php
// Requires: $page_title, $activePage, $content, $user_name, $user_email, $first_name, $profile_photo

session_start();
session_unset(); // clear all session variables
session_destroy(); // destroy session completely

header("Location: login.php");
exit();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?> | StudentCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { overflow-x: hidden; background: #f8f9fa; }
.sidebar {
    width: 250px; height: 100vh; position: fixed; top:0; left:0; background:#ff4500; color:#fff;
    display:flex; flex-direction:column; padding-top:20px; transition: width 0.3s;
}
.sidebar.collapsed { width: 80px; }
.sidebar a { color:#fff; padding:12px 20px; display:block; text-decoration:none; border-radius:6px; }
.sidebar a.active, .sidebar a:hover { background: rgba(255,255,255,0.15); }
.sidebar-header { text-align:center; font-weight:bold; padding:20px 10px; border-bottom:1px solid rgba(255,255,255,0.2); }
.sidebar.collapsed .sidebar-header h4 { display:none; }
.sidebar.collapsed a span { display:none; }

.main-content { margin-left:250px; padding:30px; transition: margin-left 0.3s; }
.main-content.collapsed { margin-left:80px; }

.topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
.top-left { display:flex; align-items:center; gap:10px; }
#toggleSidebar { background:none; border:none; font-size:1.5rem; color:#ff4500; }

.btn-primary { background-color:#1a73e8; border-color:#1a73e8; }
.btn-primary:hover { background-color:#155ab6; border-color:#155ab6; }

.profile-btn { border:none; background:none; padding:0.3rem 0.6rem; border-radius:50%; color:#fff; font-weight:600; }
.profile-menu { display:none; position:absolute; right:20px; top:60px; background:#fff; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); width:200px; overflow:hidden; z-index:100; }
.profile-menu a { display:block; padding:10px; color:#333; text-decoration:none; }
.profile-menu a:hover { background:#f1f1f1; }

</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div>
        <div class="sidebar-header">
            <h4><i class="bi bi-people-fill"></i> StudyCollabo</h4>
        </div>
        <a href="dashboard.php" class="<?= $activePage=='dashboard'?'active':'' ?>"><i class="bi bi-speedometer2 me-2"></i><span>Dashboard</span></a>
        <a href="tasks.php" class="<?= $activePage=='tasks'?'active':'' ?>"><i class="bi bi-list-task me-2"></i><span>My Tasks</span></a>
        <a href="group_tasks.php" class="<?= $activePage=='group_tasks'?'active':'' ?>"><i class="bi bi-people me-2"></i><span>Group Tasks</span></a>
        <a href="discussions.php" class="<?= $activePage=='discussions'?'active':'' ?>"><i class="bi bi-chat-left-text me-2"></i><span>Discussions</span></a>
        <a href="calendar.php" class="<?= $activePage=='calendar'?'active':'' ?>"><i class="bi bi-calendar-event me-2"></i><span>Calendar</span></a>
        <a href="resources.php" class="<?= $activePage=='resources'?'active':'' ?>"><i class="bi bi-journal-bookmark me-2"></i><span>Resources</span></a>
    </div>
    <div class="profile-section dropdown mt-auto">
        <button class="profile-btn" id="profileBtn">
            <?php if($profile_photo): ?>
                <img src="<?= htmlspecialchars($profile_photo) ?>" alt="Avatar" style="width:32px;height:32px;border-radius:50%;">
            <?php else: ?>
                <?= strtoupper(substr($first_name,0,1)) ?>
            <?php endif; ?>
        </button>
        <div class="profile-menu" id="profileMenu">
            <div style="text-align:center;padding:10px;border-bottom:1px solid #eee;">
                <?php if($profile_photo): ?>
                    <img src="<?= htmlspecialchars($profile_photo) ?>" alt="Avatar" style="width:60px;height:60px;border-radius:50%;margin-bottom:5px;">
                <?php else: ?>
                    <div style="width:60px;height:60px;border-radius:50%;background:#1a73e8;color:#fff;display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 5px;"><?= strtoupper(substr($first_name,0,1)) ?></div>
                <?php endif; ?>
                <strong><?= htmlspecialchars($first_name) ?></strong><br>
                <small><?= htmlspecialchars($user_email) ?></small>
            </div>
            <a href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a>
            <a href="../auth/logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
        </div>
    </div>
</div>

<!-- Main Content -->
<main class="main-content" id="main-content">
    <div class="topbar">
        <div class="top-left">
            <button id="toggleSidebar"><i class="bi bi-list"></i></button>
            <img src="assets/img/SClogo.png" alt="Logo" style="height:32px;width:auto;">
        </div>
    </div>

    <?= $content ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('main-content');
document.getElementById('toggleSidebar').addEventListener('click', ()=>{
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('collapsed');
});

// Profile menu toggle
const profileBtn = document.getElementById('profileBtn');
const profileMenu = document.getElementById('profileMenu');
profileBtn.addEventListener('click', ()=> profileMenu.style.display = profileMenu.style.display==='block'?'none':'block');
document.addEventListener('click', e=>{ if(!profileBtn.contains(e.target) && !profileMenu.contains(e.target)) profileMenu.style.display='none'; });
</script>
</body>
</html>