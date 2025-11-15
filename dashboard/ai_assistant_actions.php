<?php
// dashboard/ai_assistant_actions.php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$OPENAI_API_KEY = $_ENV['OPENAI_API_KEY'] ?? null;
if (!$OPENAI_API_KEY || $OPENAI_API_KEY === "your_openai_api_key_here") {
    echo json_encode(["success"=>false,"message"=>"OpenAI API key not found"]);
    exit;
}

session_start();
header('Content-Type: application/json; charset=utf-8');
require '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Not authenticated']);
    exit();
}

$user_id = intval($_SESSION['user_id']);
$action = $_POST['action'] ?? null;

// Store message helper
function store_message($conn,$user_id,$role,$message,$course=null){
    $stmt=$conn->prepare("INSERT INTO ai_chat_history (user_id, role, message, course, created_at) VALUES (?,?,?,?,NOW())");
    $stmt->bind_param("isss",$user_id,$role,$message,$course);
    $ok=$stmt->execute();
    $stmt->close();
    return $ok;
}

if($action==='send'){
    $message=trim($_POST['message'] ?? '');
    $course=trim($_POST['course'] ?? '') ?: null;
    if($message===''){ echo json_encode(['success'=>false,'message'=>'Message empty']); exit; }

    store_message($conn,$user_id,'user',$message,$course);

    // Build system prompt
    $system="You are StudyCollabo's helpful AI assistant. Provide step-by-step help. Tailor answers to course context if provided. Avoid cheating content.";
    if($course) $system.=" Course context: ".$course;

    // Use cURL with streaming
    $payload=[
        "model"=>"gpt-4o-mini",
        "messages"=>[["role"=>"system","content"=>$system],["role"=>"user","content"=>$message]],
        "max_tokens"=>800,
        "temperature"=>0.2,
        "stream"=>true
    ];

    $ch=curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,false);
    curl_setopt($ch,CURLOPT_HTTPHEADER,[
        "Content-Type: application/json",
        "Authorization: Bearer ".$OPENAI_API_KEY
    ]);
    curl_setopt($ch,CURLOPT_POST,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($payload));
    curl_setopt($ch,CURLOPT_WRITEFUNCTION,function($ch,$data){
        echo $data; 
        ob_flush(); flush(); 
        return strlen($data);
    });
    curl_setopt($ch,CURLOPT_TIMEOUT,300);

    // Enable response streaming headers
    header("Content-Type: text/event-stream");
    header("Cache-Control: no-cache");
    header("Connection: keep-alive");

    curl_exec($ch);
    $err=curl_error($ch);
    curl_close($ch);

    if($err){
        echo "event: error\ndata: ".json_encode(['message'=>"OpenAI streaming error: $err"])."\n\n";
    }
    exit();
}

// Clear chat
if($action==='clear'){
    $course=trim($_POST['course'] ?? '');
    if($course===''){
        $stmt=$conn->prepare("DELETE FROM ai_chat_history WHERE user_id=?");
        $stmt->bind_param("i",$user_id);
    } else {
        $stmt=$conn->prepare("DELETE FROM ai_chat_history WHERE user_id=? AND course=?");
        $stmt->bind_param("is",$user_id,$course);
    }
    $ok=$stmt->execute();
    $stmt->close();
    echo json_encode(['success'=>$ok,'message'=>$ok?'Cleared':'Failed']);
    exit();
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);
exit();