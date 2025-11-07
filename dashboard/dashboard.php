<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$user_name = $_SESSION['name'] ?? 'Student';
$user_role = ucfirst($_SESSION['role'] ?? 'Student');

// Fetch summary data
$personal_tasks = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE user_id = $user_id")->fetch_assoc()['total'];
$group_tasks = $conn->query("SELECT COUNT(*) AS total FROM group_tasks WHERE leader_id = $user_id OR group_id IN (SELECT group_id FROM group_members WHERE user_id = $user_id)")->fetch_assoc()['total'];
$upcoming_tasks = $conn->query("SELECT title, due_date FROM tasks WHERE user_id = $user_id AND due_date >= CURDATE() ORDER BY due_date ASC LIMIT 3");
$recent_activity = $conn->query("SELECT title, created_at FROM group_tasks ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | StudentCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>

@import url('https://fonts.googleapis.com/css2?family=Cookie&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');
body {
    background-color: #f8f9fa;
    overflow-x: hidden;
    font-family: 'Poppins', sans-serif;
}
.sidebar {
    width: 250px;
    background-color: #ff4500;
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    color: #fff;
    transition: width 0.3s;
}
.sidebar.collapsed {
    width: 80px;
}
.sidebar .nav-link {
    color: #fff;
    padding: 12px 20px;
}
.sidebar .nav-link:hover, .sidebar .nav-link.active {
    background: rgba(255,255,255,0.2);
    border-radius: 8px;
}
.sidebar .sidebar-header {
    text-align: center;
    font-weight: bold;
    padding: 20px 10px;
    border-bottom: 1px solid rgba(255,255,255,0.2);
}
.sidebar.collapsed .sidebar-header h4 {
    display: none;
}
.sidebar.collapsed .nav-link span {
    display: none;
}
.main-content {
    margin-left: 250px;
    padding: 30px;
    transition: margin-left 0.3s;
}
.collapsed + .main-content {
    margin-left: 80px;
}
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 25px;
}
.topbar .search-box {
    flex: 1 1 300px;
    min-width: 150px;
}
.dashboard-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}
.card-stat {
    flex: 1 1 220px;
    padding: 18px;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}
.card-stat h3 {
    color: #ff4500;
    font-weight: bold;
}
.recent-activity {
    margin-top: 40px;
}
.sidebar-toggle {
    background: none;
    border: none;
    color: #ff4500;
    font-size: 1.5rem;
}
.icon-stl{
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: deepskyblue;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    margin: 10px auto;
}
.icon-stl i{
    color: white;
}
.welcome-message {
    white-space: nowrap;
}

.recent-activity .card {
    border: none;
    transition: transform 0.2s, box-shadow 0.2s;
}

.recent-activity .card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.activity-icon {
    width: 40px;
    height: 40px;
    font-size: 1.2rem;
    flex-shrink: 0;
    transition: transform 0.3s;
}

.dashboard-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
}
.card-stat {
    border-radius: 12px;
    padding: 20px;
    min-width: 220px;
    transition: transform 0.2s, box-shadow 0.2s;
}
.card-stat:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}
.icon-stl {
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 50%;
    margin: 0 auto 15px auto;
}
.icon-stl i {
    font-size: 1.5rem;
}
ul {
    padding-left: 0;
    margin: 0;
}
ul li {
    line-height: 1.4;
}

.list-group-item.hover-bg:hover {
    background-color: #f8f9fa;
    cursor: pointer;
    transition: background-color 0.2s;
}


@media (max-width: 768px) {
    .topbar {
        flex-direction: column;
        align-items: flex-start;
    }
    .welcome-message {
        margin-top: 5px;
    }
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4><i class="bi bi-people-fill"></i> StudyCollabo</h4>
    </div>
    <nav class="nav flex-column">
        <a href="dashboard.php" class="nav-link active"><i class="bi bi-speedometer2 me-2"></i><span>Dashboard</span></a>
        <a href="tasks.php" class="nav-link"><i class="bi bi-list-task me-2"></i><span>My Tasks</span></a>
        <a href="group_tasks.php" class="nav-link"><i class="bi bi-people me-2"></i><span>Group Tasks</span></a>
        <a href="student_groups.php" class="nav-link"><i class="bi bi-people-fill me-2"></i><span>Student Groups</span></a>
        <a href="discussions.php" class="nav-link"><i class="bi bi-chat-left-text me-2"></i><span>Discussions</span></a>
        <a href="resources.php" class="nav-link"><i class="bi bi-journal me-2"></i><span>Resources</span></a>
        <div class="nav-item dropdown mt-2">
            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                <div class="d-flex align-items-center">
                    <div class="bg-light text-dark rounded-circle text-center me-2" style="width:30px; height:30px; line-height:30px;">
                        <?php echo strtoupper(substr($user_name,0,1) . substr(explode(" ", $user_name)[1] ?? "",0,1)); ?>
                    </div>
                    <span>Profile</span>
                </div>
            </a>
            <ul class="dropdown-menu" aria-labelledby="profileDropdown">
                <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                <li><a class="dropdown-item text-danger" href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content" id="main-content">
    <!-- Topbar -->
<div class="topbar d-flex align-items-center justify-content-between flex-wrap px-3 py-2 bg-white shadow-sm rounded-3 mb-4">
    <!-- Left: Sidebar Toggle -->
    <div class="d-flex align-items-center gap-3 mb-2 mb-md-0">
        <button class="btn btn-light border-0 d-flex align-items-center justify-content-center p-2 rounded-circle" id="toggleSidebar">
            <i class="bi bi-list fs-4 text-dark"></i>
        </button>
    </div>

    <!-- Center: Search Bar -->
    <form class="flex-grow-1 mx-3 position-relative" style="max-width: 400px;">
        <i class="bi bi-search position-absolute top-50 start-3 translate-middle-y text-muted eight-50 right-3"></i>
        <input type="text" class="form-control p-3 ps-5 w-100 rounded-pill shadow-sm" placeholder="Search tasks, groups, resources...">
    </form>

    <!-- Right: Create Dropdown -->
    <div class="d-flex align-items-center gap-2">
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle rounded-pill px-4 py-2" type="button" data-bs-toggle="dropdown" style="background: linear-gradient(135deg, #6a11cb, #2575fc); color: #fff;">
                + Create
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><a class="dropdown-item" href="tasks.php"><i class="bi bi-list-task me-2"></i>New Task</a></li>
                <li><a class="dropdown-item" href="group_tasks.php"><i class="bi bi-people me-2"></i>New Group Task</a></li>
            </ul>
        </div>
    </div>
</div>


   <!-- <h2 class="fw-normal form-floating mb-4">Welcome back, 
    <?php echo htmlspecialchars(explode(' ', trim($user_name))[0]); ?>
    </h2> -->

    <!-- Dashboard Cards -->
<div class="dashboard-cards d-flex flex-wrap gap-4">
    <div class="card-stat text-center p-4 flex-fill" style="background: linear-gradient(135deg, #ff7e5f, #feb47b); color: #fff;">
        <div class="icon-stl bg-white text-primary mb-3" style="width:60px; height:60px;">
            <i class="bi bi-person-check-fill icon-spl text-black"></i>
        </div>
        <h3 class="fw-bold mb-1"><?php echo $personal_tasks; ?></h3>
        <p class="mb-0 fw-semibold">My Tasks</p>
    </div>

    <div class="card-stat text-center p-4 flex-fill" style="background: linear-gradient(135deg, #6a11cb, #2575fc); color: #fff;">
        <div class="icon-stl bg-white text-primary mb-3" style="width:60px; height:60px;">
            <i class="bi bi-people-fill fs-3 text-black font-bold"></i>
        </div>
        <h3 class="fw-bold mb-1"><?php echo $group_tasks; ?></h3>
        <p class="mb-0 fw-semibold">Group Tasks</p>
    </div>

    <div class="card-stat text-center flex-fill" style="background: linear-gradient(135deg, #6a11cb, #007cbfff); color: #fff;">
    <div class="icon-stl bg-white text-primary mb-3" style="width:60px; height:60px;">
        <i class="bi bi-person-check-fill icon-spl text-black"></i>
    </div>
    <h3><?php echo $personal_tasks; ?></h3>
    <p>My Tasks</p>
    <div class="progress">
        <div class="progress-bar" style="width: 65%;"></div>
    </div>
    <a href="tasks.php" class="stretched-link mt-2 d-block">View all tasks <i class="bi bi-arrow-right ms-1"></i></a>
</div>

    
</div>

                <!-- Recent Activity -->
<div class="recent-activity mt-5">
    <h5 class="mb-3 text-primary fw-bold">Recent Activity</h5>
    <div class="card shadow-sm rounded-4">
        <div class="list-group list-group-flush">
            <?php if ($recent_activity->num_rows > 0): ?>
                <?php while($activity = $recent_activity->fetch_assoc()): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-4 py-3 hover-bg">
                        <div class="d-flex align-items-center gap-3">
                            <div class="activity-icon bg-info text-white rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-bell-fill"></i>
                            </div>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($activity['title']); ?></span>
                        </div>
                        <small class="text-muted"><?php echo date("M d, Y", strtotime($activity['created_at'])); ?></small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="list-group-item text-center text-muted py-4">No recent activity</div>
            <?php endif; ?>
        </div>
    </div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const toggleSidebar = document.getElementById('toggleSidebar');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('main-content');

toggleSidebar.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('collapsed');
});
</script>
</body>
</html>