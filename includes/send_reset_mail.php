<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load .env safely
require __DIR__ . '/env_loader.php';
require __DIR__ . '/../vendor/autoload.php';

function sendResetEmail($email, $resetLink) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['MAIL_USER'];
        $mail->Password   = $_ENV['MAIL_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['MAIL_PORT'];

        $mail->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - StudentCollabo';
        $mail->Body = "
            <p>Hello,</p>
            <p>We received a request to reset your password for StudentCollabo.</p>
            <p>Click the link below to reset your password:</p>
            <p><a href='{$resetLink}'>Reset My Password</a></p>
            <p>If you didn’t request this, please ignore this message.</p>
            <p>Best regards,<br>StudentCollabo Team</p>
        ";
        $mail->AltBody = "Reset your password using this link: {$resetLink}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}