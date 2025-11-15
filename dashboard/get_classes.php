<?php
session_start();
require '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit();
}

$user_id = intval($_SESSION['user_id']);
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    if ($id > 0) {
        $stmt = $conn->prepare("SELECT * FROM classes WHERE id=? AND user_id=?");
        if (!$stmt) throw new Exception('Database prepare failed.');
        $stmt->bind_param("ii",$id,$user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success'=>true,'data'=>$data]);
        exit();
    } else {
        $stmt = $conn->prepare("SELECT * FROM classes WHERE user_id=? ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), start_time");
        if (!$stmt) throw new Exception('Database prepare failed.');
        $stmt->bind_param("i",$user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success'=>true,'data'=>$data]);
        exit();
    }
} catch(Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}