<?php
include 'config.php';
session_start();
$user_id = $_SESSION['user_id'];

function isLeader($conn, $user_id, $group_id) {
    $stmt = $conn->prepare("SELECT role FROM group_members WHERE user_id=? AND group_id=?");
    $stmt->bind_param("ii", $user_id, $group_id);
    $stmt->execute();
    $stmt->bind_result($role);
    $stmt->fetch();
    $stmt->close();
    return ($role == 'leader');
}

if (isset($_POST['action'], $_POST['group_id'], $_POST['member_id'])) {
    $action = $_POST['action'];
    $group_id = intval($_POST['group_id']);
    $member_id = intval($_POST['member_id']);

    if (!isLeader($conn, $user_id, $group_id)) {
        echo 'error:not_leader';
        exit;
    }

    if ($action == 'promote') {
        $stmt = $conn->prepare("UPDATE group_members SET role='leader' WHERE user_id=? AND group_id=?");
    } elseif ($action == 'demote') {
        $stmt = $conn->prepare("UPDATE group_members SET role='member' WHERE user_id=? AND group_id=?");
    } else {
        echo 'error:invalid_action';
        exit;
    }

    $stmt->bind_param("ii", $member_id, $group_id);
    $stmt->execute();
    $stmt->close();
    echo 'success';
    exit;
}
?>