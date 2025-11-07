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

// Fetch user info
$stmt = $conn->prepare("SELECT fullname, email, user_type, image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);
    $new_role = trim($_POST['role']);
    $image_name = $user['image'];

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $new_image_name = "user_" . $user_id . "_" . time() . "." . $ext;
        $upload_path = "../uploads/" . $new_image_name;
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            $image_name = $new_image_name;
        }
    }

    $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, user_type=?, image=? WHERE id=?");
    $stmt->bind_param("ssssi", $new_name, $new_email, $new_role, $image_name, $user_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['name'] = $new_name;
    $_SESSION['role'] = $new_role;
    $_SESSION['image'] = $image_name;

   
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile Settings | StudentCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --accent: #ff4500;
    --light-gray: #f8f9fa;
    --text-dark: #333;
    --border-radius: 12px;
}
body {
    background: var(--light-gray);
    color: var(--text-dark);
    font-family: 'Inter', sans-serif;
    overflow-x: hidden;
}
.sidebar {
    width: 250px;
    background-color: var(--accent);
    position: fixed;
    top: 0; left: 0; height: 100%;
    color: #fff;
    transition: width 0.3s;
}
.sidebar.collapsed { width: 80px; }
.sidebar .nav-link {
    color: #fff; padding: 12px 20px;
    transition: background 0.2s;
}
.sidebar .nav-link:hover, .sidebar .nav-link.active {
    background: rgba(255,255,255,0.2);
    border-radius: 8px;
}
.sidebar .sidebar-header {
    text-align: center; font-weight: 600;
    padding: 25px 10px; border-bottom: 1px solid rgba(255,255,255,0.2);
}
.sidebar.collapsed .sidebar-header h4, 
.sidebar.collapsed .nav-link span { display: none; }

.main-content {
    margin-left: 250px;
    padding: 40px;
    transition: margin-left 0.3s;
}
.collapsed + .main-content { margin-left: 80px; }

.topbar {
    display: flex; justify-content: space-between;
    align-items: center; margin-bottom: 30px;
}
.sidebar-toggle {
    background: none; border: none;
    color: var(--accent); font-size: 1.8rem;
}
.profile-wrapper {
    display: flex; justify-content: center;
    align-items: center; min-height: 75vh;
}
.profile-card {
    background: #fff;
    border-radius: var(--border-radius);
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    padding: 35px 40px;
    max-width: 650px;
    width: 100%;
    animation: fadeIn 0.4s ease;
}
.profile-card img {
    width: 130px; height: 130px;
    border-radius: 50%; object-fit: cover;
    margin-bottom: 15px;
    border: 3px solid var(--accent);
    transition: transform 0.3s ease;
}
.profile-card img:hover {
    transform: scale(1.05);
}
.btn-primary {
    background-color: var(--accent);
    border: none; border-radius: 8px;
}
.btn-primary:hover {
    background-color: #e03e00;
}
label.form-label { font-weight: 600; }
.alert {
    border-radius: var(--border-radius);
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h4><i class="bi bi-people-fill me-1"></i> StudyCollabo</h4>
    </div>
    <nav class="nav flex-column">
        <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2 me-2"></i><span>Dashboard</span></a>
        <a href="tasks.php" class="nav-link"><i class="bi bi-list-task me-2"></i><span>My Tasks</span></a>
        <a href="group_tasks.php" class="nav-link"><i class="bi bi-people me-2"></i><span>Group Tasks</span></a>
        <a href="student_groups.php" class="nav-link"><i class="bi bi-people-fill me-2"></i><span>Student Groups</span></a>
        <a href="discussions.php" class="nav-link"><i class="bi bi-chat-left-text me-2"></i><span>Discussions</span></a>
        <a href="resources.php" class="nav-link"><i class="bi bi-journal me-2"></i><span>Resources</span></a>
        <a href="settings.php" class="nav-link active"><i class="bi bi-gear-fill me-2"></i><span>Settings</span></a>
        <a href="../auth/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right me-2"></i><span>Logout</span></a>
    </nav>
</div>

<!-- Main Content -->
<div class="main-content" id="main-content">
    <div class="topbar">
        <button class="sidebar-toggle" id="toggleSidebar"><i class="bi bi-list"></i></button>
        <h4 class="fw-bold text-dark mb-0">Profile Settings</h4>
    </div>

    <div class="profile-wrapper">
        <div class="profile-card text-center">
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Profile updated successfully!</div>
            <?php endif; ?>

            <img id="profilePreview" src="../uploads/<?php echo htmlspecialchars($user['image'] ?? 'default-avatar.png'); ?>" alt="Profile Image">

            <form method="POST" enctype="multipart/form-data" class="text-start">
                <div class="mb-3">
                    <label class="form-label">Profile Image</label>
                    <input type="file" name="profile_image" class="form-control" accept="image/*" onchange="previewImage(event)">
                </div>
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['fullname']); ?>" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <input type="text" name="role" value="<?php echo htmlspecialchars($user['user_type']); ?>" class="form-control" readonly>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-3 py-2 fw-semibold">
                    <i class="bi bi-save2 me-2"></i>Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const toggleSidebar = document.getElementById('toggleSidebar');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('main-content');
toggleSidebar.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('collapsed');
});

function previewImage(event) {
    const preview = document.getElementById('profilePreview');
    preview.src = URL.createObjectURL(event.target.files[0]);
}
</script>
</body>
</html>
