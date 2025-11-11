<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id    = intval($_SESSION['user_id']);
$user_name  = $_SESSION['name'] ?? 'Student';
$user_email = $_SESSION['email'] ?? 'student@example.com';
$first_name = explode(' ', trim($user_name))[0];
$profile_photo = $_SESSION['profile_photo'] ?? null;

// Get month/year from query, default to current
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year  = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Fetch user groups
$stmtG = $conn->prepare("SELECT g.id, g.group_name FROM groups g JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id=?");
$stmtG->bind_param("i", $user_id);
$stmtG->execute();
$resG = $stmtG->get_result();
$userGroupsArr = $resG->fetch_all(MYSQLI_ASSOC);
$stmtG->close();

// Fetch events for user (personal + group)
$sql = "SELECT * FROM calendar_events
        WHERE (user_id=? OR group_id IN (
            SELECT group_id FROM group_members WHERE user_id=?
        )) AND MONTH(due_date)=? AND YEAR(due_date)=?
        ORDER BY due_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $user_id, $user_id, $month, $year);
$stmt->execute();
$res = $stmt->get_result();
$events_raw = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group events by date
$events = [];
foreach ($events_raw as $e) {
    $events[$e['due_date']][] = $e;
}

// Calendar basics
$firstDayOfMonth = date('N', strtotime("$year-$month-01")); // 1=Mon
$totalDays = date('t'); 
$monthName = date('F', strtotime("$year-$month-01"));
$prevMonth = $month == 1 ? 12 : $month - 1;
$prevYear  = $month == 1 ? $year - 1 : $year;
$nextMonth = $month == 12 ? 1 : $month + 1;
$nextYear  = $month == 12 ? $year + 1 : $year;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calendar | StudyCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root { --primary:#1a73e8; --surface:#fff; --bg:#f8f9fa; --text:#202124; --muted:#5f6368; }
body { font-family:'Google Sans',sans-serif; margin:0; background:var(--bg); color:var(--text); overflow-x:hidden; }

.sidebar { width:250px; height:100vh; background:var(--surface); border-right:1px solid #ddd; position:fixed; top:0; left:0; transition: width 0.3s; overflow:hidden; }
.sidebar.collapsed { width:80px; }
.sidebar .logo { font-size:1.3rem; font-weight:600; color:var(--primary); padding:20px; display:flex; align-items:center; gap:8px; }
.sidebar ul { list-style:none; padding:0; margin:0; }
.sidebar ul li a { display:flex; align-items:center; gap:15px; padding:12px 20px; color:#333; text-decoration:none; font-weight:500; border-radius:10px; margin:5px 10px; transition:0.3s; }
.sidebar ul li a:hover, .sidebar ul li a.active { background:#e8f0fe; color:var(--primary); }

.topbar { display:flex; justify-content:space-between; align-items:center; gap:8px; height:64px; background: var(--surface); padding: 0 20px; border-bottom:1px solid #ddd; position: fixed; top:0; left:0; width:100%; z-index:99; }
.top-left { display:flex; align-items:center; gap:10px; }
.top-left img { height:32px; }

.profile-btn { border:none; background:transparent; display:flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:50%; font-weight:600; font-size:14px; color:#fff; background:var(--primary); cursor:pointer; position:relative; }
.profile-btn img { width:100%; height:100%; border-radius:50%; object-fit:cover; }
.profile-menu { position:absolute; right:0; top:50px; width:250px; background:#fff; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.1); display:none; z-index:100; }
.profile-menu.active { display:block; }
.profile-menu a { display:block; padding:10px 16px; text-decoration:none; color:#333; }
.profile-menu a:hover { background:#f5f5f5; }

main { margin-left:250px; padding:90px 30px 40px; transition: margin-left 0.3s; }
main.collapsed { margin-left:80px; }

.calendar { display:grid; grid-template-columns: repeat(7, 1fr); gap:10px; }
.calendar-header { font-weight:600; text-align:center; margin-bottom:5px; }
.day { background: #fff; border-radius:10px; padding:10px; min-height:100px; position:relative; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.2s, box-shadow 0.2s; }
.day:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.15); }
.day-number { font-weight:bold; margin-bottom:5px; }
.event { font-size:0.85rem; color:#fff; padding:2px 6px; border-radius:4px; margin-top:3px; display:block; cursor:pointer; }
.today { border:2px solid #ff4500; }
.task { background:#1a73e8; }
.group-task { background:#34a853; }
.discussion { background:#fbbc05; color:#000; }

.month-nav { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="logo"><i class="bi bi-journal-text"></i> <span>StudyCollabo</span></div>
    <ul>
        <li><a href="dashboard.php"><i class="bi bi-grid"></i> Dashboard</a></li>
        <li><a href="tasks.php"><i class="bi bi-person-check"></i> My Tasks</a></li>
        <li><a href="group_tasks.php"><i class="bi bi-people"></i> Group Tasks</a></li>
        <li><a href="calendar.php" class="active"><i class="bi bi-calendar3"></i> Calendar</a></li>
        <li><a href="discussions.php"><i class="bi bi-chat-dots"></i> Discussions</a></li>
    </ul>
</div>

<main id="main">
    <div class="topbar" id="topbar">
        <div class="top-left">
            <button class="btn btn-light" id="toggleSidebar"><i class="bi bi-list"></i></button>
            <img src="assets/img/SClogo.png" alt="StudyCollabo Logo">
        </div>
        <div class="d-flex align-items-center gap-2 position-relative">
            <button class="profile-btn" id="profileBtn">
                <?php if($profile_photo): ?><img src="<?= htmlspecialchars($profile_photo) ?>" alt="Avatar"><?php else: ?><?= strtoupper(substr($first_name,0,1)) ?><?php endif; ?>
            </button>
            <div class="profile-menu" id="profileMenu">
                <a href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a>
                <a href="../auth/logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
            </div>
        </div>
    </div>

    <div class="month-nav">
        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-outline-primary">&lt; Prev</a>
        <h3><?= $monthName ?> <?= $year ?></h3>
        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-outline-primary">Next &gt;</a>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEventModal">+ Add Event</button>
    </div>

    <div class="calendar">
        <?php
        $weekDays = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        foreach($weekDays as $wd) echo "<div class='calendar-header'>$wd</div>";
        for($i=1; $i<$firstDayOfMonth; $i++) echo "<div></div>";
        for($day=1; $day<=$totalDays; $day++){
            $date = "$year-".str_pad($month,2,'0',STR_PAD_LEFT)."-".str_pad($day,2,'0',STR_PAD_LEFT);
            $todayClass = ($date===date('Y-m-d'))?'today':'';
            echo "<div class='day $todayClass'>";
            echo "<div class='day-number'>$day</div>";
            foreach($events[$date] ?? [] as $e){
                $typeClass = htmlspecialchars($e['event_type']);
                $title = htmlspecialchars($e['title']);
                $desc  = htmlspecialchars($e['description']);
                echo "<span class='event $typeClass' title='$desc'>$title</span>";
            }
            echo "</div>";
        }
        ?>
    </div>
</main>

<!-- Add Event Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content p-3">
      <div class="modal-header">
        <h5 class="modal-title">Add Event</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="addEventForm">
            <div class="mb-2">
                <input type="text" name="title" class="form-control" placeholder="Event Title" required>
            </div>
            <div class="mb-2">
                <textarea name="description" class="form-control" placeholder="Description"></textarea>
            </div>
            <div class="mb-2">
                <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="mb-2">
                <select name="event_type" class="form-select" id="eventTypeSelect" required>
                    <option value="task">Personal Task</option>
                    <option value="group-task">Group Task</option>
                    <option value="discussion">Discussion</option>
                </select>
            </div>
            <div class="mb-2" id="groupSelectContainer" style="display:none;">
                <select name="group_id" class="form-select">
                    <option value="">Select Group</option>
                    <?php foreach($userGroupsArr as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['group_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-success w-100">Add Event</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Profile dropdown
const profileBtn = document.getElementById('profileBtn');
const profileMenu = document.getElementById('profileMenu');
profileBtn.addEventListener('click', e=>{ e.stopPropagation(); profileMenu.classList.toggle('active'); });
document.addEventListener('click', ()=> profileMenu.classList.remove('active'));

// Sidebar toggle
const toggleSidebar = document.getElementById('toggleSidebar');
const sidebar = document.getElementById('sidebar');
toggleSidebar.addEventListener('click', ()=>{
    sidebar.classList.toggle('collapsed');
    document.getElementById('main').classList.toggle('collapsed');
    document.getElementById('topbar').classList.toggle('collapsed');
});

// Event type -> show group select if needed
const eventTypeSelect = document.getElementById('eventTypeSelect');
const groupSelectContainer = document.getElementById('groupSelectContainer');
eventTypeSelect.addEventListener('change', ()=>{
    groupSelectContainer.style.display = eventTypeSelect.value === 'group-task' ? 'block' : 'none';
});

// Add Event form submit (AJAX)
document.getElementById('addEventForm').addEventListener('submit', e=>{
    e.preventDefault();
    const fd = new FormData(e.target);
    fd.append('action','add_event');
    fetch('calendar_actions.php',{method:'POST', body:fd})
        .then(r=>r.json()).then(data=>{
            if(data.success){ alert('Event added'); location.reload(); }
            else alert(data.message || 'Error adding event');
        });
});
</script>
</body>
</html>