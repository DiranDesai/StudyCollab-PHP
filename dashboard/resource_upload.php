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

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | StudyCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    
/* Root & Body */
:root { --primary:#1a73e8; --surface:#fff; --bg:#f8f9fa; --text:#202124; --muted:#5f6368; }
body { font-family:'Google Sans',sans-serif; margin:0; background:var(--bg); color:var(--text); overflow-x:hidden; }



/* Main content */
main { margin-left:250px; padding:90px 30px 40px; transition: margin-left 0.3s; }
main.collapsed { margin-left:80px; }



/* Responsive */
@media(max-width:768px){ main{margin-left:0 !important; padding-top:80px;} .sidebar{display:none;} .topbar{padding-left:20px !important;} }

</style>
</head>
<body>

<?php include '../includes/sidebar.php' ?>

<!-- Main -->
<main id="main">
    <?php include '../includes/navbar.php'; ?>

    <?php
// Handle File Upload
$upload_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['upload_resource'])) {
    $title       = trim($_POST['title']);
    $course      = trim($_POST['course']);
    $group_id    = intval($_POST['group_id']);
    $description = trim($_POST['description']);

    // File Handling
    $file_path = "";
    if (!empty($_FILES['file']['name'])) {
        $uploadDir = "../uploads/resources/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $filename = time() . "_" . basename($_FILES["file"]["name"]);
        $targetFile = $uploadDir . $filename;

        if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
            $file_path = "uploads/resources/" . $filename;
        } else {
            $upload_msg = "<p style='color:red;'>File upload failed.</p>";
        }
    }

    // Insert in database
    $stmt = $db->prepare("INSERT INTO resources(user_id, course, group_id, title, description, file_path, uploaded_at)
                          VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isisss", $user_id, $course, $group_id, $title, $description, $file_path);

    if ($stmt->execute()) {
        $upload_msg = "<p style='color:green;'>Resource uploaded successfully.</p>";
    } else {
        $upload_msg = "<p style='color:red;'>Database error: " . $stmt->error . "</p>";
    }
}
?>

<!-- Upload Form UI -->
<div class="card" style="padding:20px; margin-bottom:25px;">
    <h3>Upload New Resource</h3>
    <?= $upload_msg ?>

    <form method="POST" enctype="multipart/form-data">

        <label>Resource Title *</label>
        <input type="text" name="title" class="form-control" required>

        <label class="mt-2">Course / Subject *</label>
        <input type="text" name="course" class="form-control" required>

        <label class="mt-2">Group (Optional)</label>
        <input type="number" name="group_id" class="form-control" placeholder="Enter Group ID or leave blank">

        <label class="mt-2">Description (Optional)</label>
        <textarea name="description" rows="3" class="form-control"></textarea>

        <label class="mt-2">Upload File *</label>
        <input type="file" name="file" class="form-control" required>

        <button type="submit" name="upload_resource" class="btn btn-primary mt-3">
            Upload Resource
        </button>
    </form>
</div>



</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<script>
// Sidebar toggle
const sidebar = document.getElementById('sidebar');
const toggleSidebar = document.getElementById('toggleSidebar');
const profileBtn = document.getElementById('profileBtn');
const profileMenu = document.getElementById('profileMenu');

toggleSidebar.addEventListener('click',()=>{sidebar.classList.toggle('collapsed'); document.getElementById('main').classList.toggle('collapsed'); document.getElementById('topbar').classList.toggle('collapsed'); });

// Profile dropdown
profileBtn.addEventListener('click', e=>{ e.stopPropagation(); profileMenu.classList.toggle('active'); });
document.addEventListener('click', e=>{ if(!profileMenu.contains(e.target)) profileMenu.classList.remove('active'); });


</script>
</body>
</html>