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
        <a href="resource_library.php" class="nav-link"><i class="bi bi-journal me-2"></i><span>Resources</span></a>
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