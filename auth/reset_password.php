<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

session_start();
require '../includes/db.php';

$error = $success = "";
$token_expires_display = "";
$token_expires_ts = null; // for JS countdown

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// DEBUG MODE - log token issues (disable in production)
$debug_log_file = __DIR__ . '/reset_debug.log';
function log_token_issue($message) {
    global $debug_log_file, $token;
    $entry = date('Y-m-d H:i:s') . " | Token: " . ($token ?? 'N/A') . " | " . $message . PHP_EOL;
    file_put_contents($debug_log_file, $entry, FILE_APPEND);
}

// Check if token exists in GET
$token = $_GET['token'] ?? '';
if (!$token) {
    log_token_issue("Token missing from GET.");
    $error = "Invalid or missing token. Please <a href='forgot_password.php'>request a new password reset</a>.";
    $token = null;
}

// Fetch token expiration for tooltip and countdown
if ($token && !$success) {
    $stmt = $conn->prepare("
        SELECT reset_expires 
        FROM users 
        WHERE reset_token = ? 
        AND LOWER(user_type) = 'student'
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user_data = $result->fetch_assoc();
        $expires_ts = strtotime($user_data['reset_expires']);
        if ($expires_ts > time()) {
            $token_expires_display = date('M d, Y H:i', $expires_ts);
            $token_expires_ts = $expires_ts;
        }
    }
    $stmt->close();
}

// Handle form submission only if token exists
if ($token && isset($_POST['reset_password'])) {

    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $stmt = $conn->prepare("
            SELECT id, reset_expires 
            FROM users 
            WHERE reset_token = ? 
            AND reset_expires > NOW() 
            AND LOWER(user_type) = 'student'
        ");
        if (!$stmt) die("Database error: " . $conn->error);

        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            log_token_issue("Token not found in DB or expired.");
            $error = "Invalid or expired token. Please <a href='forgot_password.php'>request a new password reset</a>.";
        } else {
            $user = $result->fetch_assoc();
            if (strtotime($user['reset_expires']) < time()) {
                log_token_issue("Token expired in DB.");
                $error = "Token has expired. Please <a href='forgot_password.php'>request a new password reset</a>.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
                if (!$stmt2) die("Database error: " . $conn->error);

                $stmt2->bind_param("si", $hashed, $user['id']);
                if ($stmt2->execute()) {
                    $success = "Your password has been reset successfully. <a href='login.php'>Login now</a>.";
                    $token_expires_display = "";
                    $token_expires_ts = null;
                } else {
                    $error = "Failed to reset password. Please try again.";
                    log_token_issue("Failed to update password for user ID: ".$user['id']);
                }
                $stmt2->close();
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password | StudentCollabo</title>
<link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { font-family:'Google Sans',sans-serif; background:#f4f6f9; min-height:100vh; display:flex; align-items:center; justify-content:center; }
.auth-container { max-width:900px; width:100%; background:#fff; border-radius:12px; box-shadow:0 6px 15px rgba(0,0,0,0.1); overflow:hidden; display:flex; flex-direction:row; }
.auth-photo { background:url('../assets/img/study.jpg') center/cover no-repeat; flex:1; position:relative; }
.auth-photo::before { content:""; position:absolute; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.4); }
.auth-form { flex:1; padding:40px; }
.password-toggle { position: relative; }
.password-toggle i { position: absolute; top: 50%; right: 12px; transform: translateY(-50%); cursor: pointer; color: #6c757d; }
</style>
</head>
<body>
<div class="auth-container flex-column flex-md-row">
    <div class="auth-photo d-none d-md-block"></div>
    <div class="auth-form">
        <h3 class="text-center mb-4 text-primary">Reset Password</h3>

        <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <?php if(!$success && $token): ?>
        <form id="resetForm" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="mb-3 password-toggle">
                <label class="form-label">New Password</label>
                <input type="password" class="form-control" name="password" id="password" required>
                <i class="bi bi-eye-slash" id="togglePassword"></i>
            </div>

            <div class="mb-3 password-toggle">
                <label class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                <i class="bi bi-eye-slash" id="toggleConfirm"></i>
            </div>

            <?php if($token_expires_display): ?>
                <div class="form-text text-muted mb-3">
                    This reset link is valid until <strong><?php echo $token_expires_display; ?></strong>
                    <span id="countdown" style="margin-left:10px;font-weight:bold;"></span>
                </div>
            <?php endif; ?>

            <button type="submit" name="reset_password" class="btn btn-primary w-100 fw-semibold">
                Reset Password <i class="bi bi-key ms-2"></i>
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
const togglePassword = document.querySelector('#togglePassword');
const password = document.querySelector('#password');
togglePassword.addEventListener('click', function () {
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.classList.toggle('bi-eye');
    this.classList.toggle('bi-eye-slash');
});

const toggleConfirm = document.querySelector('#toggleConfirm');
const confirm = document.querySelector('#confirm_password');
toggleConfirm.addEventListener('click', function () {
    const type = confirm.getAttribute('type') === 'password' ? 'text' : 'password';
    confirm.setAttribute('type', type);
    this.classList.toggle('bi-eye');
    this.classList.toggle('bi-eye-slash');
});

// Smooth countdown timer with auto-disable, red highlight, and 7-second redirect
<?php if($token_expires_ts): ?>
let countDownDate = <?php echo $token_expires_ts * 1000; ?>;
const form = document.getElementById("resetForm");
const countdownEl = document.getElementById("countdown");
let redirectTimer = 7; // seconds for redirect

function updateCountdown() {
    let now = new Date().getTime();
    let distance = countDownDate - now;

    if (distance <= 0) {
        countdownEl.innerHTML = "Expired! Redirecting in 00:" + String(redirectTimer).padStart(2,'0');
        if (form) {
            form.querySelectorAll("input, button").forEach(el => {
                el.disabled = true;
                if (el.tagName === "INPUT") {
                    el.style.borderColor = "red";
                    el.style.backgroundColor = "#f8d7da";
                }
            });
            if (!document.getElementById("expiredAlert")) {
                form.insertAdjacentHTML('beforeend', '<div id="expiredAlert" class="alert alert-warning mt-3">This reset link has expired. Redirecting...</div>');
            }
        }
        clearInterval(countdownInterval);

        let smoothInterval = setInterval(() => {
            redirectTimer--;
            countdownEl.innerHTML = "Expired! Redirecting in 00:" + String(redirectTimer).padStart(2,'0');
            if (redirectTimer <= 0) {
                clearInterval(smoothInterval);
                window.location.href = "forgot_password.php";
            }
        }, 1000);
    } else {
        let secondsLeft = Math.floor(distance / 1000);
        let minutes = Math.floor(secondsLeft / 60);
        let seconds = secondsLeft % 60;
        countdownEl.innerHTML = "(" + String(minutes).padStart(2,'0') + ":" + String(seconds).padStart(2,'0') + " remaining)";
    }
}

let countdownInterval = setInterval(updateCountdown, 500); // update twice per second
updateCountdown();
<?php endif; ?>
</script>
</body>
</html>