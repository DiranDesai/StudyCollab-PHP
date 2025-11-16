<?php
$user_id = intval($_SESSION['user_id']);
$user_name  = $_SESSION['name'] ?? 'Student';
$user_email = $_SESSION['email'] ?? 'student@example.com';
$first_name = explode(' ', trim($user_name))[0];

?>

<style>
.logo1 {
    transform: scale(4);
    margin-left: 40px;
}

.shadow-n1{
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.219);
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
.profile-btn {
    border:none; background:transparent; display:flex;
    align-items:center; justify-content:center;
    width:36px; height:36px; border-radius:50%;
    font-weight:600; font-size:14px; color:#fff;
    background:#1a73e8; cursor:pointer; position:relative;
}
.profile-btn img { width:100%; height:100%; border-radius:50%; object-fit:cover; }
.profile-menu { position:absolute; right:0; top:50px; width:260px; background:#fff; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.1); display:none; z-index:100; }
.profile-menu.active { display:block; }
.profile-header { padding:16px; text-align:center; border-bottom:1px solid #eee; }
.profile-header img { width:60px; height:60px; border-radius:50%; margin-bottom:10px; object-fit:cover; }
.profile-header h6 { margin:0 0 4px; font-weight:600; }
.profile-header small { color:#555; }
.profile-menu a { display:block; padding:10px 16px; text-decoration:none; color:#333; }
.profile-menu a:hover { background:#f5f5f5; }

.shadow-1{
    box-shadow: rgba(0, 0, 0, 0.1) 0px 10px 15px -3px, rgba(0, 0, 0, 0.05) 0px 4px 6px -2px;
}
</style>

<div class="topbar shadow-1" id="topbar">
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
        <?php
        // Get current page filename
        $current_page = basename($_SERVER['PHP_SELF']);

        // Only show "+ Create" button if NOT on tasks.php or group_tasks.php
        if ($current_page !== 'tasks.php' && $current_page !== 'group_tasks.php'):
        ?>
            <div class="dropdown">
                <button class="btn btn-primary rounded-pill px-3" data-bs-toggle="dropdown" style="background-color:#1a73e8;">
                    + Create
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="tasks.php">New Task</a></li>
                    <li><a class="dropdown-item" href="group_tasks.php">New Group Task</a></li>
                </ul>
            </div>
        <?php endif; ?>

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
