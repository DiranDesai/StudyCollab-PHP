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

if($id>0){
    $stmt = $conn->prepare("DELETE FROM assessments WHERE id=? AND user_id=?");
    $stmt->bind_param("ii",$id,$user_id);
    if($stmt->execute()){
        echo json_encode(['success'=>true]);
    }else{
        echo json_encode(['success'=>false,'message'=>$stmt->error]);
    }
    $stmt->close();
}else{
    echo json_encode(['success'=>false,'message'=>'Invalid ID']);
}