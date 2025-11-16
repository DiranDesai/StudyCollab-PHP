<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$GEMINI_API_KEY = "AIzaSyAkEGpIEC4d3AqNtqZK-WFeP3r2Xs2mEnE";
if (!$GEMINI_API_KEY || $GEMINI_API_KEY === "AIzaSyAkEGpIEC4d3AqNtqZK-WFeP3r2Xs2mEnE") {
    echo json_encode(["success" => false, "message" => "Gemini API key not found"]);
    exit;
}

session_start();
header('Content-Type: application/json; charset=utf-8');
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = intval($_SESSION['user_id']);
$action = $_POST['action'] ?? null;

// Helper to store messages
function store_message($conn, $user_id, $role, $message, $course = null) {
    $stmt = $conn->prepare("INSERT INTO ai_chat_history (user_id, role, message, course, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $user_id, $role, $message, $course);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

if ($action === 'send') {
    $message = trim($_POST['message'] ?? '');
    $course = trim($_POST['course'] ?? '') ?: null;
    if ($message === '') {
        echo json_encode(['success' => false, 'message' => 'Message empty']);
        exit;
    }

    // Save user message
    store_message($conn, $user_id, 'user', $message, $course);

    // Build system prompt
    $system = "You are StudyCollabo's helpful AI assistant. Provide step-by-step help. Tailor answers to course context if provided. Avoid cheating content.";
    if ($course) $system .= " Course context: " . $course;

    // Gemini API payload
    $payload = [
        "model" => "gemini-1.5",
        "input" => [
            "text" => $system . "\nUser: " . $message
        ],
        "temperature" => 0.2,
        "max_output_tokens" => 800
    ];

    // Streaming is experimental in Gemini; here we do a simple POST
    $ch = curl_init("https://gemini.googleapis.com/v1/experiments:generateMessage");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $GEMINI_API_KEY"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        echo json_encode(['success' => false, 'message' => "Gemini API error: $err"]);
        exit;
    }

    $result = json_decode($response, true);
    $ai_answer = $result['candidates'][0]['content'][0]['text'] ?? 'AI could not respond';

    // Save AI response
    store_message($conn, $user_id, 'ai', $ai_answer, $course);

    echo json_encode(['success' => true, 'answer' => $ai_answer]);
    exit();
}

// Clear chat
if ($action === 'clear') {
    $course = trim($_POST['course'] ?? '');
    if ($course === '') {
        $stmt = $conn->prepare("DELETE FROM ai_chat_history WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
    } else {
        $stmt = $conn->prepare("DELETE FROM ai_chat_history WHERE user_id=? AND course=?");
        $stmt->bind_param("is", $user_id, $course);
    }
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Cleared' : 'Failed']);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
exit();
