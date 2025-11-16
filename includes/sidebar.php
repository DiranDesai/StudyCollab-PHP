<?php
// Get current file name (e.g., 'dashboard.php')
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
/* Sidebar */
.sidebar {
  width: 250px;
  height: 100vh;
  background: white;
  border-right: 1px solid #ddd;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 2;
  transition: width 0.3s;
  overflow: hidden;
}

.sidebar.collapsed {
  width: 80px;
}

.sidebar .logo {
  font-size: 1.3rem;
  font-weight: 600;
  color: var(--primary);
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
}

.sidebar ul li a:hover,
.sidebar ul li a.active {
  background: #e8f0fe;
  color: var(--primary);
}

.sidebar.collapsed ul li a span {
  display: none;
}
.shadow-1{
    box-shadow: rgba(0, 0, 0, 0.1) 0px 10px 15px -3px, rgba(0, 0, 0, 0.05) 0px 4px 6px -2px;
}
</style>

<!-- Sidebar -->
<div class="sidebar shadow-1" id="sidebar">
  <div class="logo mb-6">
    
  </div>
  <ul class="mt-5">
    <li><a href="../dashboard/dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><i class="bi bi-grid"></i> <span>Dashboard</span></a></li>
    <li><a href="tasks.php" class="<?= $current_page == 'tasks.php' ? 'active' : '' ?>"><i class="bi bi-person-check"></i> <span>My Tasks</span></a></li>
    <li><a href="group_tasks.php" class="<?= $current_page == 'group_tasks.php' ? 'active' : '' ?>"><i class="bi bi-people"></i> <span>Group Tasks</span></a></li>
    <li><a href="calendar.php" class="<?= $current_page == 'calendar.php' ? 'active' : '' ?>"><i class="bi bi-calendar3"></i> <span>Calendar</span></a></li>
    <li><a href="resource_library.php" class="<?= $current_page == 'resource_library.php' ? 'active' : '' ?>"><i class="bi bi-journal-bookmark"></i> <span>Resource Library</span></a></li>
    <li><a href="notes_section.php" class="<?= $current_page == 'notes_section.php' ? 'active' : '' ?>"><i class="bi bi-people"></i> <span>Notes Section</span></a></li>
    <li><a href="timetable_class_schedule.php" class="<?= $current_page == 'timetable_class_schedule.php' ? 'active' : '' ?>"><i class="bi bi-calendar3"></i> <span>Timetable/Class Schedule</span></a></li>
    <li><a href="exam_assignment_tracker.php" class="<?= $current_page == 'exam_assignment_tracker.php' ? 'active' : '' ?>"><i class="bi bi-journal-bookmark"></i> <span>Exam & Assignment Tracker</span></a></li>
    <li><a href="ai_assistant.php" class="<?= $current_page == 'ai_assistant.php' ? 'active' : '' ?>"><i class="bi bi-journal-bookmark"></i> <span>AI Assistant</span></a></li>
  </ul>
</div>

