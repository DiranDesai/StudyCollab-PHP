<?php
session_start();
require '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$action = $_POST['action'] ?? '';

function respond($ok,$msg){ echo json_encode(['success'=>$ok,'message'=>$msg]); exit; }

switch($action){

    // CREATE GROUP + TASK
    case 'create':
        $group_name = trim($_POST['group_name']);
        $title = trim($_POST['title']);
        $desc = trim($_POST['description']);
        $due = $_POST['due_date'];

        if($group_name=='' || $title=='') respond(false,'All fields required.');

        // Create group
        $stmt=$conn->prepare("INSERT INTO groups (group_name, leader_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("si",$group_name,$user_id);
        $stmt->execute();
        $group_id=$stmt->insert_id;
        $stmt->close();

        // Add leader to group_members
        $stmt=$conn->prepare("INSERT INTO group_members (group_name, leader_id, group_id, user_id, role, added_at) VALUES (?, ?, ?, ?, 'Leader', NOW())");
        $stmt->bind_param("siii",$group_name,$user_id,$group_id,$user_id);
        $stmt->execute();
        $stmt->close();

        // Add task
        $stmt=$conn->prepare("INSERT INTO group_tasks (group_name, leader_id, group_id, title, description, due_date, status, created_at, updated_at)
                              VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW(), NOW())");
        $stmt->bind_param("siisss",$group_name,$user_id,$group_id,$title,$desc,$due);
        $ok=$stmt->execute();
        $stmt->close();

        respond($ok,'Group and task created successfully.');
    break;

    // TOGGLE COMPLETE / REVERT
    case 'toggle_status':
        $id = intval($_POST['id']);
        $res = $conn->query("SELECT status FROM group_tasks WHERE id=$id");
        if(!$res || $res->num_rows==0) respond(false,'Task not found.');
        $current = $res->fetch_assoc()['status'];
        $new = ($current=='Pending') ? 'Completed' : 'Pending';
        $conn->query("UPDATE group_tasks SET status='$new', updated_at=NOW() WHERE id=$id");
        respond(true,"Task marked as $new.");
    break;

    // EDIT TASK
    case 'edit':
        $id = intval($_POST['id']);
        $title = trim($_POST['title']);
        $desc = trim($_POST['description']);
        $due = $_POST['due_date'];

        $stmt=$conn->prepare("UPDATE group_tasks SET title=?, description=?, due_date=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("sssi",$title,$desc,$due,$id);
        $ok=$stmt->execute();
        respond($ok,'Task updated successfully.');
    break;

    // DELETE TASK
    case 'delete':
        $id=intval($_POST['id']);
        $ok=$conn->query("DELETE FROM group_tasks WHERE id=$id");
        respond($ok,'Task deleted.');
    break;

    // INVITE MEMBER
    case 'invite':
        $group_id=intval($_POST['group_id']);
        $email=trim($_POST['email']);
        if($email=='') respond(false,'Email required.');

        $user=$conn->query("SELECT id FROM users WHERE email='$email'");
        if(!$user||$user->num_rows==0) respond(false,'User not found.');
        $uid=$user->fetch_assoc()['id'];

        // Fetch group name + leader id
        $grp=$conn->query("SELECT group_name, leader_id FROM groups WHERE id=$group_id");
        if(!$grp||$grp->num_rows==0) respond(false,'Group not found.');
        $g=$grp->fetch_assoc();
        $group_name=$g['group_name'];
        $leader_id=$g['leader_id'];

        // Check if already member
        $exists=$conn->query("SELECT id FROM group_members WHERE group_id=$group_id AND user_id=$uid");
        if($exists && $exists->num_rows>0) respond(false,'User already a member.');

        $stmt=$conn->prepare("INSERT INTO group_members (group_name, leader_id, group_id, user_id, role, added_at)
                              VALUES (?, ?, ?, ?, 'Member', NOW())");
        $stmt->bind_param("siii",$group_name,$leader_id,$group_id,$uid);
        $ok=$stmt->execute();
        respond($ok,'Member invited successfully.');
    break;

    default:
        respond(false,'Invalid action.');
}
?>