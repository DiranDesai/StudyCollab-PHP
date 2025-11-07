<?php
session_start();
require '../config/db.php';

$error = "";

if (isset($_POST['register'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // encrypt password
    $user_type = 'student'; // default for student registration
    $course = trim($_POST['course']);

    // Check if email already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $error = "Email already registered!";
    } else {
        // Insert new student
        $stmt_insert = $conn->prepare("INSERT INTO users (fullname, email, password, user_type, course) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("sssss", $fullname, $email, $password, $user_type, $course);

        if ($stmt_insert->execute()) {
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['name'] = $fullname;
            $_SESSION['role'] = $user_type;
            header("Location: ../dashboard/dashboard.php");
            exit();
        } else {
            $error = "Registration failed: " . $stmt_insert->error;
        }
        $stmt_insert->close();
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Registration | StudentCollabo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
    background: #f4f6f9;
}
.register-card {
    max-width: 500px;
    margin: 50px auto;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    background: #fff;
}
</style>
</head>
<body>

<div class="register-card">
    <h3 class="text-center mb-4 text-primary">Student Registration</h3>
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="mb-3">
            <label for="fullname" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="fullname" name="fullname" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label for="course" class="form-label">Select Course</label>
            <select class="form-select" id="course" name="course" required>
                <option value="" disabled selected>Choose your course</option>
                <option value="Information Technology">Information Technology</option>
                <option value="Business Administration">Business Administration</option>
                <option value="Accounting">Accounting</option>
                <option value="Marketing">Marketing</option>
                <!-- Add more courses here -->
            </select>
        </div>
        <button type="submit" name="register" class="btn btn-primary w-100">Register</button>
        <p class="text-center mt-3">Already have an account? <a href="login.php">Login here</a></p>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>