<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status"=>"error","message"=>"Not logged in"]);
    exit();
}

$user_id = intval($_SESSION['user_id']);
$action  = $_POST['action'] ?? '';
$id      = intval($_POST['id'] ?? 0);

/* ==========================
        DELETE CLASS
   ========================== */
if ($action === "delete") {

    $stmt = $conn->prepare("
        UPDATE timetable 
        SET is_deleted = 1 
        WHERE id=? AND user_id=?
    ");
    $stmt->bind_param("ii", $id, $user_id);

    if ($stmt->execute()) {
        echo json_encode(["status"=>"success", "message"=>"Class deleted"]);
    } else {
        echo json_encode(["status"=>"error", "message"=>$conn->error]);
    }
    exit();
}

/* ==========================
        RESTORE CLASS
   ========================== */
if ($action === "restore") {

    $stmt = $conn->prepare("
        UPDATE timetable 
        SET is_deleted = 0 
        WHERE id=? AND user_id=? AND is_deleted=1
    ");
    $stmt->bind_param("ii", $id, $user_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(["status"=>"success", "message"=>"Class restored"]);
    } else {
        echo json_encode(["status"=>"error", "message"=>"Nothing to restore"]);
    }
    exit();
}

echo json_encode(["status"=>"error","message"=>"Invalid action"]);
?>