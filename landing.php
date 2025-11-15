<?php
// landing.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StudyCollabo - Collaborate Smarter</title>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- AOS Animations -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
<!-- Custom CSS -->
<style>
body {
    font-family: 'Segoe UI', sans-serif;
    scroll-behavior: smooth;
}

.hero {
    background: linear-gradient(to right, #1a73e8, #4285f4);
    color: white;
    padding: 120px 20px;
    text-align: center;
}

.hero h1 {
    font-size: 3rem;
    font-weight: bold;
}

.hero p {
    font-size: 1.3rem;
    margin: 20px 0;
}

.section {
    padding: 80px 20px;
}

.bg-light-gradient {
    background: linear-gradient(to right, #f5f7fa, #c3cfe2);
}

.btn-primary {
    background-color: #1a73e8;
    border: none;
    padding: 12px 30px;
    font-size: 1.1rem;
    border-radius: 50px;
}

.feature-icon {
    font-size: 3rem;
    color: #1a73e8;
    margin-bottom: 15px;
}

.screenshot {
    width: 100%;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
}

footer {
    background: #1a73e8;
    color: white;
    padding: 40px 20px;
}
</style>
</head>
<body>

<!-- Hero Section with Student Banner -->
<section class="hero" style="position: relative; overflow: hidden;">
    <!-- Background Banner -->
    <div style="
        background: url('assets/img/student-banner.jpg') center center/cover no-repeat;
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        z-index: 0;
        filter: brightness(0.6);
    "></div>

    <div class="container" style="position: relative; z-index: 1;">
        <h1 data-aos="fade-up">Welcome to StudyCollabo</h1>
        <p data-aos="fade-up" data-aos-delay="200">
            Your all-in-one platform for student project and assignment collaboration.
        </p>
        <a href="auth/register.php" class="btn btn-primary btn-lg" data-aos="fade-up" data-aos-delay="400">Get Started</a>
    </div>
</section>

<!-- Features Section -->
<section class="section bg-light-gradient">
    <div class="container">
        <h2 class="text-center mb-5" data-aos="fade-up">Features</h2>
        <div class="row text-center g-4">
            <div class="col-md-4" data-aos="zoom-in" data-aos-delay="100">
                <i class="fas fa-tasks feature-icon"></i>
                <h4>Task Management</h4>
                <p>Create, assign, and track tasks effortlessly with visual indicators.</p>
            </div>
            <div class="col-md-4" data-aos="zoom-in" data-aos-delay="200">
                <i class="fas fa-file-upload feature-icon"></i>
                <h4>File Sharing</h4>
                <p>Upload, download, and manage project resources with version control.</p>
            </div>
            <div class="col-md-4" data-aos="zoom-in" data-aos-delay="300">
                <i class="fas fa-comments feature-icon"></i>
                <h4>Collaboration</h4>
                <p>Engage in project discussions with dedicated chat boards.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="section">
    <div class="container">
        <h2 class="text-center mb-5" data-aos="fade-up">How It Works</h2>
        <div class="row g-4 align-items-center">
            <div class="col-md-6" data-aos="fade-right">
                <img src="images/dashboard-screenshot.png" alt="Dashboard Screenshot" class="screenshot">
            </div>
            <div class="col-md-6" data-aos="fade-left">
                <ol>
                    <li>Sign up and create or join a group.</li>
                    <li>Assign tasks, upload files, and set deadlines.</li>
                    <li>Collaborate via chat or discussion boards.</li>
                    <li>Monitor progress and complete projects efficiently.</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<!-- Groups & Personal Tasks and Projects Preview Section -->
<section class="section bg-light-gradient">
    <div class="container">
        <h2 class="text-center mb-5" data-aos="fade-up">Groups & Personal Tasks and Projects Preview</h2>
        <div class="row g-5 align-items-center">

            <!-- Groups Preview -->
            <div class="col-md-6" data-aos="fade-right">
                <img src="images/groups-preview.png" alt="Groups and Projects Preview" class="screenshot">
            </div>
            <div class="col-md-6" data-aos="fade-left">
                <h4>Group Projects</h4>
                <p>Collaborate with your peers efficiently by joining or creating groups. Within each group, you can:</p>
                <ul>
                    <li><strong>Assign and track tasks</strong> with deadlines and progress indicators.</li>
                    <li><strong>Share files securely</strong> and manage versions for all group members.</li>
                    <li><strong>Discuss ideas and updates</strong> in real-time using chat or discussion boards.</li>
                    <li><strong>Monitor overall project progress</strong> to stay on top of deadlines.</li>
                </ul>
                <a href="register.php" class="btn btn-primary mt-3">Join a Group Today</a>
            </div>

            <!-- Personal Projects Preview -->
            <div class="col-md-6 order-md-2" data-aos="fade-left">
                <img src="images/personal-tasks-preview.png" alt="Personal Tasks and Projects Preview" class="screenshot">
            </div>
            <div class="col-md-6 order-md-1" data-aos="fade-right">
                <h4>Personal Tasks & Projects</h4>
                <p>Manage your individual tasks and projects with ease. StudyCollabo allows you to:</p>
                <ul>
                    <li><strong>Create personal tasks</strong> with deadlines and reminders.</li>
                    <li><strong>Track progress</strong> visually and mark completed items.</li>
                    <li><strong>Organize projects</strong> by subject, course, or priority.</li>
                    <li><strong>Stay on top of your responsibilities</strong> even outside group work.</li>
                </ul>
                <a href="register.php" class="btn btn-primary mt-3">Start Personal Projects</a>
            </div>
        </div>
    </div>
</section>

<!-- Benefits Section -->
<section class="section bg-light-gradient">
    <div class="container">
        <h2 class="text-center mb-5" data-aos="fade-up">Why StudyCollabo?</h2>
        <div class="row text-center g-4">
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-clock feature-icon"></i>
                <p>Save Time and Stay Organized</p>
            </div>
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                <i class="fas fa-users feature-icon"></i>
                <p>Centralized Team Collaboration</p>
            </div>
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
                <i class="fas fa-check-circle feature-icon"></i>
                <p>Track Task Completion and Accountability</p>
            </div>
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="400">
                <i class="fas fa-file-alt feature-icon"></i>
                <p>Secure File Management with Version Control</p>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer>
    <div class="container text-center">
        <p>&copy; 2025 StudyCollabo. All Rights Reserved.</p>
        <p><a href="auth/login.php" class="text-white">Login</a> | <a href="auth/register.php" class="text-white">Sign Up</a></p>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- AOS JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
AOS.init({
    duration: 1000,
    once: true
});
</script>
</body>
</html>