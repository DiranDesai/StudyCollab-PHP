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

// Avatar
$profile_photo = $_SESSION['profile_photo'] ?? null;

// Fetch data
$total_tasks     = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
$completed_tasks = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE user_id = $user_id AND status='completed'")->fetch_assoc()['total'] ?? 0;
$group_tasks     = $conn->query("
    SELECT COUNT(*) AS total 
    FROM group_tasks 
    WHERE leader_id = $user_id 
    OR group_id IN (SELECT group_id FROM group_members WHERE user_id = $user_id)
")->fetch_assoc()['total'] ?? 0;

$progress = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

// Fetch recent 5 tasks from user's groups only
$recent_activity = $conn->query("
    SELECT gt.title, gt.status, gt.created_at, u.fullname AS author, u.image 
    FROM group_tasks gt
    JOIN users u ON gt.leader_id = u.id
    WHERE gt.group_id IN (SELECT group_id FROM group_members WHERE user_id = $user_id)
    ORDER BY gt.created_at DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | StudyCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* Apple-style UI */
:root {
    --primary:#007aff;
    --bg:#f5f5f7;
    --card:#ffffff;
    --radius:18px;
}

body {
    font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto;
    background:var(--bg);
}

/* Dashboard layout */
main {
    margin-left:250px;
    padding:90px 30px 40px;
}

/* Card */
.card-apple {
    background:var(--card);
    padding:22px;
    border-radius:var(--radius);
    border:1px solid #e5e5e7;
    box-shadow:0 4px 20px rgba(0,0,0,0.04);
    transition:all 0.25s ease;
}

.card-apple:hover {
    transform:translateY(-3px);
    box-shadow:0 8px 28px rgba(0,0,0,0.07);
}

.stat-number {
    font-size:2.4rem;
    font-weight:700;
    color:var(--primary);
}

/* Activity */
.activity-card {
    border-radius:var(--radius);
    overflow:hidden;
    animation:fadeIn 0.6s ease both;
}

@keyframes fadeIn {
    from {opacity:0; transform:translateY(8px);}
    to {opacity:1; transform:translateY(0);}
}

.activity-item {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:16px 20px;
    border-bottom:1px solid #f1f1f3;
    transition:background .2s;
}

.activity-item:hover {
    background:#fafafa;
}

.activity-left {
    display:flex;
    gap:15px;
    align-items:center;
}

/* Avatar */
.avatar {
    width:45px;
    height:45px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid #eee;
}

/* Status badge */
.status-badge {
    padding:6px 14px;
    border-radius:20px;
    font-size:0.75rem;
    font-weight:600;
    color:white;
    text-transform:capitalize;
}

.status-completed { background:#34c759; }
.status-inprogress { background:#0a84ff; }
.status-pending { background:#ff9f0a; }
.status-overdue { background:#ff453a; }
.status-review { background:#af52de; }

/* Status icons */
.status-icon {
    font-size:1.4rem;
}

.icon-completed { color:#34c759; }
.icon-inprogress { color:#0a84ff; }
.icon-pending { color:#ff9f0a; }
.icon-overdue { color:#ff453a; }
.icon-review { color:#af52de; }

.activity-title { font-weight:600; font-size:1rem; }
.activity-author { font-size:0.8rem; color:#6e6e73; }
.activity-date { font-size:0.78rem; color:#8e8e93; }

</style>
</head>

<body>

<?php include '../includes/sidebar.php' ?>

<main>

<?php include '../includes/navbar.php'; ?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card-apple">
            <i class="bi bi-person-check-fill text-primary fs-3"></i>
            <div class="stat-number"><?= $total_tasks ?></div>
            <div class="fw-semibold">My Tasks</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card-apple">
            <i class="bi bi-people-fill text-success fs-3"></i>
            <div class="stat-number"><?= $group_tasks ?></div>
            <div class="fw-semibold">Group Tasks</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card-apple">
            <i class="bi bi-graph-up text-info fs-3"></i>
            <div class="stat-number"><?= $progress ?>%</div>
            <div class="fw-semibold">Progress</div>
        </div>
    </div>
</div>

<!-- Activity -->
<h5 class="fw-semibold mt-4 mb-2">Recent Activity</h5>

<div class="card-apple activity-card">

<?php if ($recent_activity->num_rows > 0): ?>
    <?php while($a = $recent_activity->fetch_assoc()): ?>

        <?php  
            $status = strtolower(str_replace(" ", "", $a['status']));
            $iconClass = "icon-" . $status;
            $badgeClass = "status-" . $status;
        ?>

        <div class="activity-item">
            <div class="activity-left">

                <!-- Status Icon -->
                <i class="bi bi-dot status-icon <?= $iconClass ?>"></i>

                <!-- Avatar -->
                <img src="<?= $a['image'] ?: '../assets/default.jpg' ?>" class="avatar">

                <div>
                    <div class="activity-title"><?= htmlspecialchars($a['title']) ?></div>
                    <div class="activity-author">
                        by <?= htmlspecialchars($a['author']) ?>
                    </div>
                    <div class="activity-date">
                        <?= date("M d, Y • H:i", strtotime($a['created_at'])) ?>
                    </div>
                </div>
            </div>

            <span class="status-badge <?= $badgeClass ?>">
                <?= htmlspecialchars($a['status']) ?>
            </span>
        </div>

    <?php endwhile; ?>
<?php else: ?>
    <div class="p-4 text-center text-muted">No recent activity</div>
<?php endif; ?>

</div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Sidebar toggle
const sidebar = document.getElementById('sidebar');
const toggleSidebar = document.getElementById('toggleSidebar');
const profileBtn = document.getElementById('profileBtn');
const profileMenu = document.getElementById('profileMenu');

toggleSidebar.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    document.getElementById('main').classList.toggle('collapsed');
    document.getElementById('topbar').classList.toggle('collapsed');
});

// Profile dropdown
profileBtn.addEventListener('click', e => {
    e.stopPropagation();
    profileMenu.classList.toggle('active');
});

document.addEventListener('click', e => {
    if (!profileMenu.contains(e.target)) profileMenu.classList.remove('active');
});

// AJAX search
const searchBox = document.getElementById('searchBox');
const searchResults = document.getElementById('searchResults');

searchBox.addEventListener('input', () => {
    const query = searchBox.value.trim();

    if (query.length < 2) {
        searchResults.style.display = 'none';
        return;
    }

    fetch('search_suggestions.php?q=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            searchResults.innerHTML = '';

            if (data.length > 0) {
                data.forEach(item => {
                    const li = document.createElement('li');
                    li.textContent = item.title;
                    li.style.padding = '8px 12px';
                    li.style.cursor = 'pointer';
                    li.onclick = () => window.location.href = 'task_view.php?id=' + item.id;
                    searchResults.appendChild(li);
                });
                searchResults.style.display = 'block';
            } else {
                searchResults.innerHTML = '<li class="text-muted">No results found</li>';
                searchResults.style.display = 'block';
            }
        });
});
</script>


</body>
</html>
