<?php
// discussions.php
session_start();

// ✅ Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require '../includes/db.php';

// ✅ Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id       = intval($_SESSION['user_id']);
$user_name     = $_SESSION['name'] ?? 'Student';
$user_email    = $_SESSION['email'] ?? 'student@example.com';
$first_name    = explode(' ', trim($user_name))[0];
$profile_photo = $_SESSION['profile_photo'] ?? null;

// ✅ Ensure discussion_replies table exists (safe)
$createRepliesSql = "
CREATE TABLE IF NOT EXISTS discussion_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discussion_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (discussion_id) REFERENCES group_discussions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$conn->query($createRepliesSql);

// ✅ Fetch user groups
$stmtG = $conn->prepare("SELECT g.id, g.group_name, g.leader_id 
                         FROM groups g 
                         JOIN group_members gm ON gm.group_id = g.id 
                         WHERE gm.user_id = ?");
$stmtG->bind_param("i", $user_id);
$stmtG->execute();
$resG = $stmtG->get_result();
$userGroupsArr = $resG->fetch_all(MYSQLI_ASSOC);
$stmtG->close();

// ✅ Fetch group discussions
$stmt = $conn->prepare("
    SELECT d.id, d.group_id, d.user_id, d.title, d.content, d.sent_at, g.group_name, g.leader_id
    FROM group_discussions d
    JOIN groups g ON d.group_id = g.id
    JOIN group_members gm ON gm.group_id = g.id
    WHERE gm.user_id = ?
    ORDER BY d.sent_at DESC
    LIMIT 100
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$discussions_res = $stmt->get_result();
$discussions = $discussions_res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ✅ Map leader names
$leaderIds = [];
foreach ($discussions as $d) { 
    $leaderIds[$d['leader_id']] = true; 
}
$leaderNames = [];
if (!empty($leaderIds)) {
    $ids = array_keys($leaderIds);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $sql = "SELECT id, fullname FROM users WHERE id IN ($placeholders)";
    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param($types, ...$ids);
    $stmt2->execute();
    $r = $stmt2->get_result();
    while ($row = $r->fetch_assoc()) {
        $leaderNames[$row['id']] = $row['fullname'];
    }
    $stmt2->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Group Discussions | StudyCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root { --primary:#1a73e8; --surface:#fff; --bg:#f8f9fa; --text:#202124; --muted:#5f6368; }
body { font-family:'Google Sans',sans-serif; background:var(--bg); color:var(--text); margin:0; overflow-x:hidden; }

.sidebar { width:250px; height:100vh; background:var(--surface); border-right:1px solid #ddd; position:fixed; top:0; left:0; transition: width 0.3s; overflow:hidden; }
.sidebar.collapsed { width:80px; }
.sidebar .logo { font-size:1.3rem; font-weight:600; color:var(--primary); padding:20px; display:flex; align-items:center; gap:8px; }
.sidebar ul { list-style:none; padding:0; margin:0; }
.sidebar ul li a { display:flex; align-items:center; gap:15px; padding:12px 20px; color:#333; text-decoration:none; border-radius:10px; margin:5px 10px; transition:0.3s; font-weight:500; }
.sidebar ul li a:hover, .sidebar ul li a.active { background:#e8f0fe; color:var(--primary); }

.topbar { display:flex; justify-content:space-between; align-items:center; height:64px; background:var(--surface); padding:0 20px; border-bottom:1px solid #ddd; position:fixed; top:0; left:0; width:100%; z-index:99; }

.profile-btn { border:none; background:transparent; display:flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:50%; color:#fff; background:var(--primary); font-weight:600; cursor:pointer; }
.profile-btn img { width:100%; height:100%; border-radius:50%; object-fit:cover; }
.profile-menu { position:absolute; right:0; top:50px; width:300px; background:#fff; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.1); display:none; z-index:100; }
.profile-menu.active { display:block; }
.profile-header { padding:16px; text-align:center; border-bottom:1px solid #eee; }
.profile-header img { width:60px; height:60px; border-radius:50%; margin-bottom:10px; object-fit:cover; }
.profile-header h6 { margin:0 0 4px; font-weight:600; }
.profile-header small { color:#555; }
.profile-menu a { display:block; padding:10px 16px; color:#333; text-decoration:none; }
.profile-menu a:hover { background:#f5f5f5; }

main { margin-left:250px; padding:90px 30px 40px; transition:margin-left 0.3s; }
main.collapsed { margin-left:80px; }

.discussion-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px; margin-top:20px; }
.discussion-card { background:var(--surface); border:1px solid #e0e0e0; border-radius:12px; padding:16px; display:flex; flex-direction:column; gap:8px; }
.discussion-title { font-weight:600; font-size:1.05rem; }
.discussion-meta { color:#666; font-size:0.85rem; }
.discussion-content { color:#333; font-size:0.95rem; white-space:pre-wrap; max-height:120px; overflow:auto; }
.reply-list { margin-top:10px; border-top:1px solid #eee; padding-top:8px; max-height:220px; overflow:auto; }
.reply { background:#fafafa; border-radius:8px; padding:8px; font-size:0.92rem; margin-bottom:5px; }
.reply small { color:#666; font-size:0.8rem; display:block; margin-top:5px; }

@media(max-width:768px){ main{margin-left:0!important;padding-top:80px;} .sidebar{display:none;} }
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="logo"><i class="bi bi-journal-text"></i> <span>StudyCollabo</span></div>
    <ul>
        <li><a href="dashboard.php"><i class="bi bi-grid"></i>Dashboard</a></li>
        <li><a href="tasks.php"><i class="bi bi-person-check"></i>My Tasks</a></li>
        <li><a href="group_tasks.php"><i class="bi bi-people"></i>Group Tasks</a></li>
        <li><a href="calendar.php"><i class="bi bi-calendar3"></i>Calendar</a></li>
        <li><a href="discussions.php" class="active"><i class="bi bi-chat-dots"></i>Discussions</a></li>
    </ul>
</div>

<main id="main">
    <div class="topbar" id="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-light" id="toggleSidebar"><i class="bi bi-list"></i></button>
            <img src="assets/img/SClogo.png" alt="Logo" style="height:32px;">
        </div>

        <div class="search-box mx-auto">
            <i class="bi bi-search"></i>
            <input id="liveSearch" type="text" placeholder="Search discussions..." class="form-control rounded-pill ps-5">
        </div>

        <!-- ✅ Profile button only (no +Create) -->
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
                        <div style="width:60px;height:60px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:600;margin:0 auto 10px;">
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

    <!-- Create Discussion -->
    <div class="mt-4">
        <h5>Start a Discussion</h5>
        <div class="card p-3 mt-2">
            <form id="createDiscussionForm">
                <div class="row g-2">
                    <div class="col-md-4">
                        <select name="group_id" class="form-select" required>
                            <option value="">Select Group</option>
                            <?php foreach($userGroupsArr as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['group_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <input name="title" class="form-control" placeholder="Discussion title" required>
                    </div>
                </div>
                <textarea name="content" rows="3" class="form-control mt-2" placeholder="Discussion content..." required></textarea>
                <div class="text-end mt-2">
                    <button class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Create Discussion</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Discussions list -->
    <h5 class="mt-4">Discussions</h5>
    <div class="discussion-cards">
        <?php if($discussions): foreach($discussions as $d): ?>
            <div class="discussion-card" id="discussion-<?= $d['id'] ?>">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="discussion-title"><?= htmlspecialchars($d['title']) ?></div>
                        <div class="discussion-meta">Group: <?= htmlspecialchars($d['group_name']) ?> — by <?= htmlspecialchars($leaderNames[$d['leader_id']] ?? 'Leader') ?></div>
                    </div>
                    <small class="text-muted"><?= date("M d, Y H:i", strtotime($d['sent_at'])) ?></small>
                </div>
                <div class="discussion-content"><?= nl2br(htmlspecialchars($d['content'])) ?></div>
            </div>
        <?php endforeach; else: ?>
            <p class="text-muted">No discussions yet.</p>
        <?php endif; ?>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar=document.getElementById('sidebar');
document.getElementById('toggleSidebar').onclick=()=>{sidebar.classList.toggle('collapsed');document.getElementById('main').classList.toggle('collapsed');};
const profileBtn=document.getElementById('profileBtn'),menu=document.getElementById('profileMenu');
profileBtn.onclick=e=>{e.stopPropagation();menu.classList.toggle('active');};
document.onclick=e=>{if(!menu.contains(e.target)&&!profileBtn.contains(e.target))menu.classList.remove('active');};
</script>
</body>
</html>