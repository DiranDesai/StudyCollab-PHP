<?php

session_start();

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header("Location: ../dashboard/dashboard.php");
    exit();
}

require '../includes/db.php';

$error = "";

// Handle login
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    // Prepare statement with error handling
    $stmt = $conn->prepare("SELECT id, fullname, password, user_type FROM users WHERE email = ?");
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['fullname'];
            $_SESSION['role'] = $user['user_type'];

            if ($remember) {
                $token = bin2hex(random_bytes(16));
                $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                $expiry_datetime = date('Y-m-d H:i:s', $expiry);

                // Minimal update: store token & expiry
                $stmt2 = $conn->prepare("UPDATE users SET remember_token = ?, remember_expiry = ? WHERE id = ?");
                if (!$stmt2) {
                    die("Database error (remember update): " . $conn->error);
                }
                $stmt2->bind_param("ssi", $token, $expiry_datetime, $user['id']);
                $stmt2->execute();
                $stmt2->close();

                setcookie("remember_me", $user['id'] . ":" . $token, $expiry, "/", "", isset($_SERVER['HTTPS']), true);
            }

            header("Location: ../dashboard/dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }

    $stmt->close();
}

// Auto-login if remember_me cookie exists
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    list($userId, $token) = explode(":", $_COOKIE['remember_me']);

    $stmt = $conn->prepare("SELECT id, fullname, user_type, UNIX_TIMESTAMP(remember_expiry) as expiry, remember_token FROM users WHERE id = ?");
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if ($user['remember_token'] === $token && time() < $user['expiry']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['fullname'];
            $_SESSION['role'] = $user['user_type'];
            header("Location: ../dashboard/dashboard.php");
            exit();
        } else {
            setcookie("remember_me", "", time() - 3600, "/", "", isset($_SERVER['HTTPS']), true);
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | StudyCollabo</title>
<link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">

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
    max-width: 950px;
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
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.auth-photo::before {
    content: "";
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: linear-gradient(to bottom right, rgba(26,115,232,0.7), rgba(66,133,244,0.7));
    z-index: 1;
}

.hero-text {
    position: relative;
    color: #fff;
    text-align: center;
    z-index: 2;
    animation: fadeInUp 1.2s ease forwards;
    padding: 0 20px;
}

.hero-text h2 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 15px;
    color: fbbc05;
}

.hero-description {
    font-size: 1rem;
    margin-bottom: 20px;
    max-width: 320px;
    margin-left: auto;
    margin-right: auto;
}

.btn-discover {
    background-color: #fff;
    color: #1a73e8;
    font-weight: 600;
    border-radius: 50px;
    padding: 10px 25px;
    text-decoration: none;
    transition: all 0.3s ease, transform 0.3s ease;
}

.btn-discover:hover {
    background-color: #e8f0fe;
    color: #155ab6;
    transform: scale(1.05);
}

@keyframes fadeInUp {
    0% { opacity: 0; transform: translateY(30px);}
    100% { opacity: 1; transform: translateY(0);}
}

.auth-form {
    flex: 1;
    padding: 40px;
}

@media (max-width: 767px) {
    .hero-text h2 { font-size: 1.6rem; }
    .hero-text .hero-description {
        font-size: 0.95rem;
        max-width: 90%;
        padding: 0 15px;
    }
    .btn-discover {
        padding: 8px 20px;
        font-size: 0.95rem;
    }
}
</style>
</head>
<body>

<div class="auth-container flex-column flex-md-row">
    <!-- Hero Image Panel -->
    <div class="auth-photo d-none d-md-flex">
        <div class="hero-text" data-aos="fade-up">
            <h2>Discover StudyCollabo</h2>
            <p class="hero-description">
                Collaborate smarter, track tasks, and manage personal and group projects efficiently.
            </p>
            <a href="landing.php" class="btn btn-discover">Learn More</a>
        </div>
    </div>

    <!-- Login Form -->
    <div class="auth-form">
        <h3 class="text-center mb-4 text-primary">StudyCollabo Login</h3>

        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', this)">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                <label class="form-check-label" for="remember">Remember Me</label>
            </div>

            <button type="submit" name="login" class="btn btn-primary w-100 d-flex justify-content-center align-items-center fw-semibold">
                Login <i class="bi bi-box-arrow-in-right ms-2"></i>
            </button>

            <p class="text-center mt-3">
                Don't have an account? <a href="register.php">Register here</a><br>
                <a href="forgot_password.php">Forgot Password?</a>
            </p>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
  AOS.init({
    duration: 1200,
    once: true,
    easing: 'ease-in-out'
  });

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