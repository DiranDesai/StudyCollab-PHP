<?php
session_start();
require '../includes/db.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$id = intval($_POST['id'] ?? 0);
$type = $_POST['type'] ?? '';
$title = $_POST['title'] ?? '';
$course = $_POST['course'] ?? '';
$due_date = $_POST['due_date'] ?? '';
$notes = $_POST['notes'] ?? '';

if(empty($type) || empty($title) || empty($due_date)){
    echo json_encode(['success'=>false,'message'=>'Please fill required fields']);
    exit;
}

if($id>0){
    $stmt = $conn->prepare("UPDATE assessments SET type=?, title=?, course=?, due_date=?, notes=? WHERE id=? AND user_id=?");
    $stmt->bind_param("ssssiii", $type, $title, $course, $due_date, $notes, $id, $user_id);
}else{
    $stmt = $conn->prepare("INSERT INTO assessments (user_id,type,title,course,due_date,notes) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("isssss",$user_id,$type,$title,$course,$due_date,$notes);
}

if($stmt->execute()){
    echo json_encode(['success'=>true]);
}else{
    echo json_encode(['success'=>false,'message'=>$stmt->error]);
}
$stmt->close();