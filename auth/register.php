<?php
/* ────────────────────────────────
   🔐 Secure Session Setup (before start)
──────────────────────────────── */
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

session_start();
require '../includes/db.php';

$error = $success = "";

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if (isset($_POST['register'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('CSRF validation failed.');
    }

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];
    $user_type = 'Student'; // Automatically set

    // Basic validations
    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already registered.";
        } else {
            // Insert new user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("INSERT INTO users (fullname, email, password, user_type, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt2->bind_param("ssss", $fullname, $email, $hashed, $user_type);
            if ($stmt2->execute()) {
                $success = "Registration successful! <a href='login.php'>Login here</a>.";
            } else {
                $error = "Failed to register. Try again.";
            }
            $stmt2->close();
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
<title>Register | StudentCollabo</title>
<!-- Google Sans Font -->
<link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
    font-family: 'Google Sans', sans-serif;
    background: #f4f6f9;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.auth-container {
    max-width: 900px;
    width: 100%;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    display: flex;
    flex-direction: row;
}
.auth-photo {
    background: url('../assets/img/study.jpg') center center/cover no-repeat;
    min-height: 100%;
    flex: 1;
    position: relative;
}
.auth-photo::before {
    content: "";
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.4);
    border-radius: inherit;
}
.auth-form {
    flex: 1;
    padding: 40px;
}
</style>
</head>
<body>

<div class="auth-container flex-column flex-md-row">
    <div class="auth-photo d-none d-md-block"></div>

    <div class="auth-form">
        <h3 class="text-center mb-4 text-primary">Register</h3>

        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="mb-3">
                <label for="fullname" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="fullname" name="fullname" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <!-- Password field with input-group toggle -->
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', this)">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <label for="confirm" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="confirm" name="confirm" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm', this)">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="register" class="btn btn-primary w-100 d-flex justify-content-center align-items-center fw-semibold">
                Register <i class="bi bi-person-plus ms-2"></i>
            </button>

            <p class="text-center mt-3">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Password toggle function (Bootstrap input-group style)
function togglePassword(fieldId, btn) {
    const field = document.getElementById(fieldId);
    const icon = btn.querySelector("i");
    if (field.type === "password") {
        field.type = "text";
        icon.classList.remove("bi-eye-fill");
        icon.classList.add("bi-eye-slash-fill");
    } else {
        field.type = "password";
        icon.classList.remove("bi-eye-slash-fill");
        icon.classList.add("bi-eye-fill");
    }
}
</script>
</body>
</html>