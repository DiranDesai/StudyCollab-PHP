<?php
session_start();
header('Content-Type: application/json');
require '../includes/db.php';

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$data = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';

if($action === 'history'){
    $res = $conn->query("SELECT role,message,course,created_at FROM ai_chat_history WHERE user_id=$user_id ORDER BY created_at ASC");
    $history = [];
    while($row = $res->fetch_assoc()){
        $history[] = $row;
    }
    echo json_encode($history);
    exit;
}

// Otherwise handle new AI message
$message = trim($data['message'] ?? '');
$course = trim($data['course'] ?? null);

if(!$message){
    echo json_encode(['success'=>false,'message'=>'Empty message']);
    exit;
}

// --- Build course context ---
$context_text = '';
if($course){
    $assignments = $conn->query("SELECT title,due_date FROM assignments WHERE user_id=$user_id AND course='". $conn->real_escape_string($course) ."'");
    while($a = $assignments->fetch_assoc()){
        $context_text .= "- Assignment: {$a['title']} (Due: {$a['due_date']})\n";
    }
    $quizzes = $conn->query("SELECT title,due_date FROM quizzes WHERE user_id=$user_id AND course='". $conn->real_escape_string($course) ."'");
    while($q = $quizzes->fetch_assoc()){
        $context_text .= "- Quiz: {$q['title']} (Date: {$q['due_date']})\n";
    }
    $notes = $conn->query("SELECT note FROM notes WHERE user_id=$user_id AND course='". $conn->real_escape_string($course) ."'");
    while($n = $notes->fetch_assoc()){
        $context_text .= "- Note: {$n['note']}\n";
    }
}

// --- Call AI API ---
$prompt = "You are a helpful student AI assistant.\n";
$prompt .= "User Question: $message\n";
if($course){
    $prompt .= "Course: $course\nContext:\n$context_text\n";
}
$prompt .= "Provide a clear, detailed answer, and study tips if relevant.";

// Example: Using OpenAI GPT API (replace YOUR_API_KEY)
$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer YOUR_API_KEY'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model'=>'gpt-4',
    'messages'=>[['role'=>'user','content'=>$prompt]],
    'temperature'=>0.7
]));
$response = curl_exec($ch);
curl_close($ch);
$result = json_decode($response,true);
$ai_answer = $result['choices'][0]['message']['content'] ?? 'AI could not respond';

// --- Save chat history ---
$stmt = $conn->prepare("INSERT INTO ai_chat_history(user_id, role, message, course) VALUES (?,?,?,?)");
$stmt->bind_param("isss",$user_id,$role,$msg,$course_val);

// Save user message
$role = 'user'; $msg = $message; $course_val = $course; $stmt->execute();
// Save AI response
$role = 'ai'; $msg = $ai_answer; $stmt->execute();

echo json_encode(['success'=>true,'answer'=>$ai_answer]);