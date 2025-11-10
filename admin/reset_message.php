<?php
// reset_message.php
session_start();
if (!isset($_SESSION['reset_success'])) {
    header('Location: forgot_password.php');
    exit();
}

$message = $_SESSION['reset_success'];
unset($_SESSION['reset_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Email Sent - RFID GPMS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #e1e7f0, #b0caf0);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .message-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            max-width: 500px;
            width: 100%;
            text-align: center;
            padding: 40px;
        }
    </style>
</head>
<body>
    <div class="message-container">
        <div class="mb-4">
            <i class="fas fa-envelope-circle-check text-success" style="font-size: 4rem;"></i>
        </div>
        <h3 class="text-success mb-3">Reset Email Sent!</h3>
        <p class="mb-4"><?php echo htmlspecialchars($message); ?></p>
        <p class="text-muted mb-4">
            Please check your email inbox and spam folder for the password reset link.
            <br><small>The link will expire in 1 hour.</small>
        </p>
        <div class="d-grid gap-2">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Login
            </a>
            <a href="forgot_password.php" class="btn btn-outline-secondary">
                <i class="fas fa-redo me-2"></i>Resend Reset Link
            </a>
        </div>
    </div>
</body>
</html>