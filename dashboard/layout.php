<?php
// layout.php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_name = $_SESSION['user_name'] ?? "User";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyCollabo Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f4f6f9;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }
        .sidebar {
            width: 250px;
            background: #0d1b2a;
            color: white;
            flex-shrink: 0;
            height: 100vh;
            position: fixed;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            display: block;
            padding: 12px 20px;
            transition: 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: #1b263b;
        }
        .main-content {
            flex-grow: 1;
            margin-left: 250px;
            padding: 30px;
        }
        .navbar-custom {
            background: #1b263b;
            color: white;
            padding: 12px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #ff4500;
        }
        .navbar-custom h4 {
            margin: 0;
            font-weight: 600;
        }
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .btn-orange {
            background-color: #ff4500;
            color: #fff;
        }
        .btn-orange:hover {
            background-color: #e03e00;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h3 class="text-center py-3 border-bottom border-light">STUDCOLLAB</h3>
    <a href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
    <a href="tasks.php"><i class="fas fa-tasks me-2"></i> Personal Tasks</a>
    <a href="group_tasks.php" class="active"><i class="fas fa-users me-2"></i> Group Tasks</a>
    <a href="resources.php"><i class="fas fa-folder-open me-2"></i> Resources</a>
    <a href="profile.php"><i class="fas fa-user me-2"></i> Profile</a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
</div>

<div class="main-content">
    <div class="navbar-custom">
        <h4>Welcome, <?php echo htmlspecialchars($user_name); ?></h4>
    </div>

    <div class="content mt-4">