<?php
// dashboard/ai_assistant_history.php
header('Content-Type: application/json; charset=utf-8');

// Start session safely
if (session_status() == PHP_SESSION_NONE) session_start();

require '../includes/db.php'; // $conn

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = intval($_SESSION['user_id']);
$course = $_GET['course'] ?? null;
$limit = 200; // default maximum messages

if ($course === '') $course = null;

try {
    if ($course) {
        $stmt = $conn->prepare("
            SELECT id, role, message, course, created_at 
            FROM ai_chat_history 
            WHERE user_id = ? AND course = ? 
            ORDER BY created_at ASC 
            LIMIT ?
        ");
        if (!$stmt) throw new Exception("DB prepare failed");
        $stmt->bind_param("isi", $user_id, $course, $limit);
    } else {
        $stmt = $conn->prepare("
            SELECT id, role, message, course, created_at 
            FROM ai_chat_history 
            WHERE user_id = ? 
            ORDER BY created_at ASC 
            LIMIT ?
        ");
        if (!$stmt) throw new Exception("DB prepare failed");
        $stmt->bind_param("ii", $user_id, $limit);
    }

    if (!$stmt->execute()) throw new Exception("DB execute failed");

    $res = $stmt->get_result();
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = [
            'id' => (int)$row['id'],
            'role' => $row['role'],
            'message' => $row['message'],
            'course' => $row['course'],
            'created_at' => $row['created_at']
        ];
    }

    $stmt->close();

    echo json_encode(['success' => true, 'data' => $data]);
    exit();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}
