<?php
// discussions_actions.php
session_start();
require '../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$action = $_POST['action'] ?? '';

switch($action){
    case 'create':
        $group_id = intval($_POST['group_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if(!$group_id || !$title || !$content){
            echo json_encode(['success'=>false,'message'=>'All fields required']);
            exit;
        }

        // ensure user is member of group
        $stmt = $conn->prepare("SELECT 1 FROM group_members WHERE group_id=? AND user_id=?");
        $stmt->bind_param("ii",$group_id,$user_id);
        $stmt->execute();
        $r = $stmt->get_result();
        if($r->num_rows === 0){
            echo json_encode(['success'=>false,'message'=>'You are not a member of that group']);
            exit;
        }

        $stmt2 = $conn->prepare("INSERT INTO group_discussions (group_id, user_id, title, content, sent_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt2->bind_param("iiss", $group_id, $user_id, $title, $content);
        if($stmt2->execute()){
            echo json_encode(['success'=>true,'discussion_id'=>$stmt2->insert_id]);
        } else echo json_encode(['success'=>false,'message'=>'DB error']);
        exit;
    break;

    case 'get_replies':
        $discussion_id = intval($_POST['discussion_id'] ?? 0);
        if(!$discussion_id){ echo json_encode([]); exit; }

        // ensure discussion belongs to a group user is in
        $stmt = $conn->prepare("SELECT 1 FROM group_discussions d JOIN groups g ON d.group_id=g.id JOIN group_members gm ON gm.group_id=g.id WHERE d.id=? AND gm.user_id=?");
        $stmt->bind_param("ii",$discussion_id,$user_id);
        $stmt->execute();
        $r = $stmt->get_result();
        if($r->num_rows === 0){ echo json_encode([]); exit; }

        $stmt2 = $conn->prepare("SELECT dr.id, dr.message, dr.sent_at, u.fullname AS name FROM discussion_replies dr JOIN users u ON dr.user_id=u.id WHERE dr.discussion_id=? ORDER BY dr.sent_at ASC");
        $stmt2->bind_param("i",$discussion_id);
        $stmt2->execute();
        $res = $stmt2->get_result();
        $out = [];
        while($row = $res->fetch_assoc()) $out[] = $row;
        echo json_encode($out);
        exit;
    break;

    case 'post_reply':
        $discussion_id = intval($_POST['discussion_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        if(!$discussion_id || !$message){ echo json_encode(['success'=>false,'message'=>'Invalid']); exit; }

        // ensure discussion belongs to a group user is in
        $stmt = $conn->prepare("SELECT 1 FROM group_discussions d JOIN groups g ON d.group_id=g.id JOIN group_members gm ON gm.group_id=g.id WHERE d.id=? AND gm.user_id=?");
        $stmt->bind_param("ii",$discussion_id,$user_id);
        $stmt->execute();
        $r = $stmt->get_result();
        if($r->num_rows === 0){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }

        $stmt2 = $conn->prepare("INSERT INTO discussion_replies (discussion_id, user_id, message, sent_at) VALUES (?, ?, ?, NOW())");
        $stmt2->bind_param("iis",$discussion_id, $user_id, $message);
        if($stmt2->execute()) echo json_encode(['success'=>true]);
        else echo json_encode(['success'=>false,'message'=>'DB error']);
        exit;
    break;

    case 'delete':
        $discussion_id = intval($_POST['id'] ?? 0);
        if(!$discussion_id){ echo json_encode(['success'=>false]); exit; }

        // only leader of that group can delete
        $stmt = $conn->prepare("DELETE d FROM group_discussions d JOIN groups g ON d.group_id=g.id WHERE d.id=? AND g.leader_id=?");
        $stmt->bind_param("ii",$discussion_id,$user_id);
        if($stmt->execute() && $stmt->affected_rows>0) echo json_encode(['success'=>true]);
        else echo json_encode(['success'=>false,'message'=>'Unauthorized or not found']);
        exit;
    break;

    case 'search':
        $q = trim($_POST['query'] ?? '');
        $out = [];
        if($q){
            $like = "%{$q}%";
            $stmt = $conn->prepare("SELECT d.id, d.title, g.group_name FROM group_discussions d JOIN groups g ON d.group_id=g.id JOIN group_members gm ON gm.group_id=g.id WHERE gm.user_id=? AND (d.title LIKE ? OR d.content LIKE ?) LIMIT 10");
            $stmt->bind_param("iss",$user_id, $like, $like);
            $stmt->execute();
            $res = $stmt->get_result();
            while($r = $res->fetch_assoc()) $out[] = $r;
        }
        echo json_encode($out);
        exit;
    break;

    case 'invite_member':
        $group_id = intval($_POST['group_id'] ?? 0);
        $email = trim($_POST['email'] ?? '');
        if(!$group_id || !$email){ echo json_encode(['success'=>false,'message'=>'Missing']); exit; }

        // only leader can invite
        $stmt = $conn->prepare("SELECT leader_id FROM groups WHERE id=?");
        $stmt->bind_param("i",$group_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if(!$row || $row['leader_id'] != $user_id){ echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }

        // find user by email
        $stmt2 = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt2->bind_param("s",$email);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $u = $res2->fetch_assoc();
        if(!$u){ echo json_encode(['success'=>false,'message'=>'User not found']); exit; }
        $invite_id = intval($u['id']);

        // check membership
        $stmt3 = $conn->prepare("SELECT 1 FROM group_members WHERE group_id=? AND user_id=?");
        $stmt3->bind_param("ii",$group_id,$invite_id);
        $stmt3->execute();
        $r3 = $stmt3->get_result();
        if($r3->num_rows>0){ echo json_encode(['success'=>false,'message'=>'User already a member']); exit; }

        // add member
        $stmt4 = $conn->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'Member')");
        $stmt4->bind_param("ii",$group_id,$invite_id);
        if($stmt4->execute()) echo json_encode(['success'=>true]);
        else echo json_encode(['success'=>false,'message'=>'DB error']);
        exit;
    break;

    default:
        echo json_encode(['success'=>false,'message'=>'Invalid action']);
}