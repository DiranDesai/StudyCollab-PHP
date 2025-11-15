<?php
// sidebar.php

// Get current file name
$current_page = basename($_SERVER['PHP_SELF']);
$user_id = $_SESSION['user_id'] ?? 0;

// Fetch counts
$myTasks = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE user_id='$user_id'")->fetch_assoc()['total'] ?? 0;
$groupTasks = $conn->query("
    SELECT COUNT(*) AS total 
    FROM group_members gtm 
    JOIN group_tasks gt ON gtm.id = gt.id
    WHERE gtm.user_id='$user_id'
")->fetch_assoc()['total'] ?? 0;
$resources = $conn->query("SELECT COUNT(*) AS total FROM resources")->fetch_assoc()['total'] ?? 0;
$notes = $conn->query("SELECT COUNT(*) AS total FROM notes WHERE user_id='$user_id'")->fetch_assoc()['total'] ?? 0;
$assignments = $conn->query("SELECT COUNT(*) AS total FROM assessments WHERE user_id='$user_id'")->fetch_assoc()['total'] ?? 0;
$calendarEvents = $conn->query("SELECT COUNT(*) AS total FROM calendar_events WHERE user_id='$user_id'")->fetch_assoc()['total'] ?? 0;
?>

<style>
/* Sidebar */
.sidebar {
  width: 250px;
  height: 100vh;
  background: var(--surface, #fff);
  position: fixed;
  top: 0;
  left: 0;
  transition: width 0.3s;
  overflow: hidden;
  z-index: 1;
  border-right: 1px solid #ddd;
}

.shadow-n1 {
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.219);
}

.sidebar.collapsed {
  width: 80px;
}

.sidebar .logo {
  font-size: 1.3rem;
  font-weight: 600;
  color: var(--primary, #007aff);
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.sidebar ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.sidebar ul li a {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 12px 20px;
  color: #333;
  text-decoration: none;
  font-weight: 500;
  border-radius: 10px;
  margin: 5px 10px;
  transition: 0.3s;
  position: relative;
}

.sidebar ul li a:hover,
.sidebar ul li a.active {
  background: #e8f0fe;
  color: var(--primary, #007aff);
}

.sidebar.collapsed ul li a span {
  display: none;
}

.count-badge {
  background: var(--primary, #007aff);
  color: white;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  margin-left: auto;
}

@media(max-width:768px){
  .sidebar{width:0; display:none;}
}
</style>

<!-- Sidebar HTML -->
<div class="sidebar shadow-n1" id="sidebar">
  <div class="logo mb-5"></div>
  
  <ul class="mt-7">
    <li>
      <a href="../dashboard/dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
        <i class="bi bi-grid"></i> <span>Dashboard</span>
      </a>
    </li>

    <li>
      <a href="tasks.php" class="<?= $current_page == 'tasks.php' ? 'active' : '' ?>">
        <i class="bi bi-person-check"></i>
        <span>My Tasks</span>
        <span class="count-badge"><?= $myTasks ?></span>
      </a>
    </li>

    <li>
      <a href="group_tasks.php" class="<?= $current_page == 'group_tasks.php' ? 'active' : '' ?>">
        <i class="bi bi-people"></i>
        <span>Group Tasks</span>
        <span class="count-badge"><?= $groupTasks ?></span>
      </a>
    </li>

    <li>
      <a href="calendar.php" class="<?= $current_page == 'calendar.php' ? 'active' : '' ?>">
        <i class="bi bi-calendar3"></i>
        <span>Calendar</span>
        <!-- <span class="count-badge"><?= $calendarEvents ?></span> -->
      </a>
    </li>

    <li>
      <a href="resource_library.php" class="<?= $current_page == 'resource_library.php' ? 'active' : '' ?>">
        <i class="bi bi-journal-bookmark"></i>
        <span>Resource Library</span>
        <span class="count-badge"><?= $resources ?></span>
      </a>
    </li>

    <li>
      <a href="notes_section.php" class="<?= $current_page == 'notes_section.php' ? 'active' : '' ?>">
        <i class="bi bi-people"></i>
        <span>Notes Section</span>
        <span class="count-badge"><?= $notes ?></span>
      </a>
    </li>

    <li>
      <a href="timetable_class_schedule.php" class="<?= $current_page == 'timetable_class_schedule.php' ? 'active' : '' ?>">
        <i class="bi bi-calendar3"></i>
        <span>Timetable/Class Schedule</span>
      </a>
    </li>

    <li>
      <a href="exam_assignment_tracker.php" class="<?= $current_page == 'exam_assignment_tracker.php' ? 'active' : '' ?>">
        <i class="bi bi-journal-bookmark"></i>
        <span>Exam & Assignment Tracker</span>
        <span class="count-badge"><?= $assignments ?></span>
      </a>
    </li>

    <li>
      <a href="ai_assistant.php" class="<?= $current_page == 'ai_assistant.php' ? 'active' : '' ?>">
        <i class="bi bi-journal-bookmark"></i><span>AI Assistant</span>
      </a>
    </li>
  </ul>
</div>

<!-- Sidebar Toggle Script -->
<script>
// const sidebar = document.getElementById('sidebar');
// const toggleSidebar = document.getElementById('toggleSidebar');

// toggleSidebar?.addEventListener('click', () => {
//     sidebar.classList.toggle('collapsed');
// });
</script>
