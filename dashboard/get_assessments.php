<?php
session_start();
require '../includes/db.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$id = intval($_GET['id'] ?? 0);

if($id>0){
    $stmt = $conn->prepare("SELECT * FROM assessments WHERE user_id=? AND id=?");
    $stmt->bind_param("ii",$user_id,$id);
}else{
    $stmt = $conn->prepare("SELECT * FROM assessments WHERE user_id=? ORDER BY due_date ASC");
    $stmt->bind_param("i",$user_id);
}

$stmt->execute();
$res = $stmt->get_result();
$data = [];
while($row = $res->fetch_assoc()){
    $data[] = $row;
}
$stmt->close();

echo json_encode(['success'=>true,'data'=>$data]);