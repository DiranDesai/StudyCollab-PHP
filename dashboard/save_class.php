<?php
session_start();
require '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit();
}

$user_id = intval($_SESSION['user_id']);
$action  = $_POST['action'] ?? '';

if (!isset($_SESSION['deleted_class'])) $_SESSION['deleted_class'] = null;

try {
    if ($action === 'create' || $action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $day = trim($_POST['day'] ?? '');
        $start_time = $_POST['start_time'] ?? '';
        $end_time   = $_POST['end_time'] ?? '';

        if (!$title || !$day || !$start_time || !$end_time) {
            throw new Exception('Missing required fields.');
        }

        if ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO classes (user_id,title,course,location,day,start_time,end_time,created_at) VALUES (?,?,?,?,?,?,?,NOW())");
            if (!$stmt) throw new Exception('Database prepare failed.');
            $stmt->bind_param("issssss", $user_id,$title,$course,$location,$day,$start_time,$end_time);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success'=>true,'message'=>'Class added.']);
            exit();
        } else {
            // update
            $stmt = $conn->prepare("UPDATE classes SET title=?, course=?, location=?, day=?, start_time=?, end_time=? WHERE id=? AND user_id=?");
            if (!$stmt) throw new Exception('Database prepare failed.');
            $stmt->bind_param("ssssssii",$title,$course,$location,$day,$start_time,$end_time,$id,$user_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success'=>true,'message'=>'Class updated.']);
            exit();
        }

    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        // fetch before delete for undo
        $stmt = $conn->prepare("SELECT * FROM classes WHERE id=? AND user_id=?");
        if (!$stmt) throw new Exception('Database prepare failed.');
        $stmt->bind_param("ii",$id,$user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $class = $res->fetch_assoc();
        $stmt->close();

        if ($class) {
            $_SESSION['deleted_class'] = $class; // save for undo
            $stmt = $conn->prepare("DELETE FROM classes WHERE id=? AND user_id=?");
            if (!$stmt) throw new Exception('Database prepare failed.');
            $stmt->bind_param("ii",$id,$user_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success'=>true,'message'=>'Class deleted.']);
            exit();
        } else {
            throw new Exception('Class not found.');
        }

    } elseif ($action === 'undo') {
        if ($_SESSION['deleted_class']) {
            $c = $_SESSION['deleted_class'];
            $stmt = $conn->prepare("INSERT INTO classes (id,user_id,title,course,location,day,start_time,end_time,created_at) VALUES (?,?,?,?,?,?,?,?,?)");
            if (!$stmt) throw new Exception('Database prepare failed.');
            $stmt->bind_param("iisssssss",$c['id'],$c['user_id'],$c['title'],$c['course'],$c['location'],$c['day'],$c['start_time'],$c['end_time'],$c['created_at']);
            $stmt->execute();
            $stmt->close();
            $_SESSION['deleted_class'] = null;
            echo json_encode(['success'=>true,'message'=>'Class restored.']);
            exit();
        } else {
            throw new Exception('No class to restore.');
        }

    } else {
        throw new Exception('Invalid action.');
    }
} catch(Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}