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
$user_image = $_SESSION['image'] ?? 'default-avatar.png'; // assuming profile image filename is stored in session

// Fetch discussions
$discussions = $conn->query("
    SELECT d.id, d.title, d.content, d.user_id, d.created_at, u.name AS author, u.image AS author_image
    FROM discussions d
    JOIN users u ON d.user_id = u.id
    ORDER BY d.created_at DESC
");

// Handle new discussion submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['content'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);

    $conn->query("INSERT INTO discussions (title, content, user_id, created_at) VALUES ('$title', '$content', $user_id, NOW())");
    header("Location: discussions.php"); // Refresh page
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Discussions | StudentCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; overflow-x: hidden; }
.sidebar { width: 250px; background-color: #ff4500; position: fixed; top: 0; left: 0; height: 100%; color: #fff; transition: width 0.3s; }
.sidebar.collapsed { width: 80px; }
.sidebar .nav-link { color: #fff; padding: 12px 20px; }
.sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.2); border-radius: 8px; }
.sidebar .sidebar-header { text-align: center; font-weight: bold; padding: 20px 10px; border-bottom: 1px solid rgba(255,255,255,0.2); }
.sidebar.collapsed .sidebar-header h4 { display: none; }
.sidebar.collapsed .nav-link span { display: none; }
.main-content { margin-left: 250px; padding: 30px; transition: margin-left 0.3s; }
.collapsed + .main-content { margin-left: 80px; }
.topbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 25px; }
.sidebar-toggle { background: none; border: none; color: #ff4500; font-size: 1.5rem; }
.welcome-message { white-space: nowrap; }
.discussion-card { background: #fff; border-radius: 10px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); display: flex; gap: 10px; }
.discussion-card img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
.discussion-card-content { flex: 1; }
.discussion-card h5 { color: #ff4500; margin: 0 0 5px 0; }
.discussion-card small { display: block; color: #888; }
.discussion-form { margin-bottom: 30px; }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4><i class="bi bi-people-fill"></i> StudyCollabo</h4>
    </div>
    <nav class="nav flex-column">
        <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2 me-2"></i><span>Dashboard</span></a>
        <a href="tasks.php" class="nav-link"><i class="bi bi-list-task me-2"></i><span>My Tasks</span></a>
        <a href="group_tasks.php" class="nav-link"><i class="bi bi-people me-2"></i><span>Group Tasks</span></a>
        <a href="student_groups.php" class="nav-link"><i class="bi bi-people-fill me-2"></i><span>Student Groups</span></a>
        <a href="discussions.php" class="nav-link active"><i class="bi bi-chat-left-text me-2"></i><span>Discussions</span></a>
        <a href="resources.php" class="nav-link"><i class="bi bi-journal me-2"></i><span>Resources</span></a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content" id="main-content">
    <div class="topbar">
        <button class="sidebar-toggle" id="toggleSidebar"><i class="bi bi-list"></i></button>
        <div class="welcome-message text-dark"><strong>Welcome</strong> <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_role); ?>)</div>
    </div>

    <!-- New Discussion Form -->
    <div class="discussion-form">
        <h4>Start a New Discussion</h4>
        <form method="POST" action="">
            <div class="mb-3">
                <input type="text" name="title" class="form-control" placeholder="Discussion Title" required>
            </div>
            <div class="mb-3">
                <textarea name="content" class="form-control" rows="4" placeholder="Type your discussion content..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Post Discussion</button>
        </form>
    </div>

    <!-- Discussions List -->
    <div class="discussions-list">
        <h4>Recent Discussions</h4>
        <?php if($discussions->num_rows > 0): ?>
            <?php while($d = $discussions->fetch_assoc()): ?>
                <div class="discussion-card">
                    <img src="../uploads/<?php echo htmlspecialchars($d['author_image'] ?? 'default-avatar.png'); ?>" alt="Profile Image">
                    <div class="discussion-card-content">
                        <h5><?php echo htmlspecialchars($d['title']); ?></h5>
                        <p><?php echo nl2br(htmlspecialchars($d['content'])); ?></p>
                        <small>By <?php echo htmlspecialchars($d['author']); ?> on <?php echo date('M d, Y H:i', strtotime($d['created_at'])); ?></small>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No discussions yet. Start the first discussion!</p>
        <?php endif; ?>
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
