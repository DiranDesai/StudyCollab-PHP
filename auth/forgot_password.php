<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

session_start();
require '../includes/db.php';
require '../includes/send_reset_mail.php'; // path fixed

$error = $success = "";

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if (isset($_POST['reset_request'])) {

    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token.');
    }

    $email = trim($_POST['email']);

    // Only students can reset
    $stmt = $conn->prepare("SELECT id, fullname FROM users WHERE email = ? AND user_type='student'");
    if (!$stmt) { die("Prepare failed: (" . $conn->errno . ") " . $conn->error); }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiry = time() + 60 * 30; // 30 min expiry

        // Store token and expiry in DB
        $stmt2 = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=FROM_UNIXTIME(?) WHERE id=?");
        $stmt2->bind_param("sii", $token, $expiry, $user['id']);
        $stmt2->execute();
        $stmt2->close();

        // Build reset link
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";

        // Send email
        if (sendResetEmail($email, $resetLink)) {
            $success = "A password reset link has been sent to your email address.";
        } else {
            $error = "Failed to send reset email. Please try again later.";
        }

    } else {
        $error = "No student account found with that email.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password | StudentCollabo</title>
<link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { font-family:'Google Sans',sans-serif; background:#f4f6f9; min-height:100vh; display:flex; align-items:center; justify-content:center; }
.auth-container { max-width:900px; width:100%; background:#fff; border-radius:12px; box-shadow:0 6px 15px rgba(0,0,0,0.1); overflow:hidden; display:flex; flex-direction:row; }
.auth-photo { background:url('../assets/img/study.jpg') center/cover no-repeat; flex:1; position:relative; }
.auth-photo::before { content:""; position:absolute; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.4); }
.auth-form { flex:1; padding:40px; }
</style>
</head>
<body>
<div class="auth-container flex-column flex-md-row">
    <div class="auth-photo d-none d-md-block"></div>

    <div class="auth-form">
        <h3 class="text-center mb-4 text-primary">Forgot Password</h3>
        <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="mb-3">
                <label for="email" class="form-label">Enter your registered email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <button type="submit" name="reset_request" class="btn btn-primary w-100 fw-semibold">
                Send Reset Link <i class="bi bi-envelope ms-2"></i>
            </button>

            <p class="text-center mt-3">
                <a href="login.php">Back to Login</a>
            </p>
        </form>
    </div>
</div>
</body>
</html>