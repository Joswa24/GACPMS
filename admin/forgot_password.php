<?php
// forgot_password.php - FIXED VERSION

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../connection.php';

// Generate CSRF token if not exists
if (empty($_SESSION['forgot_csrf_token'])) {
    $_SESSION['forgot_csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['forgot_csrf_time'] = time();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['forgot_csrf_token'])) {
        $error = "Security token missing. Please refresh the page and try again.";
    } elseif ($_POST['csrf_token'] !== $_SESSION['forgot_csrf_token']) {
        $error = "Security token invalid or expired. Please refresh the page and try again.";
        $_SESSION['forgot_csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // Token is valid - process the form
        $email = trim($_POST['email']);
        $captcha = trim($_POST['captcha']);
        
        // Validate inputs
        if (empty($email) || empty($captcha)) {
            $error = "Please fill in all fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            // Verify CAPTCHA
            $session_captcha = isset($_SESSION['captcha']) ? strtolower(trim($_SESSION['captcha'])) : '';
            $input_captcha = strtolower(trim($captcha));
            
            if (empty($session_captcha) || $input_captcha !== $session_captcha) {
                $error = "Invalid CAPTCHA code. Please try again.";
                unset($_SESSION['captcha']);
            } else {
                // CAPTCHA is valid - check if email exists
                try {
                    $stmt = $db->prepare("SELECT id, username FROM user WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $user = $result->fetch_assoc();
                        
                        // Generate verification token
                        $token = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                        
                        // Store token in database
                        $stmt = $db->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                        $stmt->bind_param("iss", $user['id'], $token, $expires);
                        
                        if ($stmt->execute()) {
                            // Clear the used CAPTCHA and CSRF token
                            unset($_SESSION['captcha']);
                            unset($_SESSION['forgot_csrf_token']);
                            
                            // Send verification email using PHPMailer
                            if (sendPasswordResetEmail($email, $token, $user['username'])) {
                                $_SESSION['reset_success'] = "Password reset link has been sent to your email!";
                                header('Location: reset_message.php');
                                exit();
                            } else {
                                $error = "Failed to send verification email. Please try again.";
                            }
                        } else {
                            $error = "Database error. Please try again.";
                        }
                    } else {
                        $error = "No account found with that email address.";
                    }
                } catch (Exception $e) {
                    error_log("Forgot password error: " . $e->getMessage());
                    $error = "Database error. Please try again.";
                }
            }
        }
    }
}

// Function to send password reset email using PHPMailer
function sendPasswordResetEmail($email, $token, $username) {
    try {
        // Load PHPMailer classes
        require_once 'PHPMailer/src/PHPMailer.php';
        require_once 'PHPMailer/src/SMTP.php';
        require_once 'PHPMailer/src/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'joshuapastorpide10@gmail.com'; // Your Gmail
        $mail->Password = 'bmnvognbjqcpxcyf'; // Your App Password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Enable verbose debug output
        $mail->SMTPDebug = 0; // Set to 2 for detailed debug output
        $mail->Debugoutput = 'error_log';
        
        // Recipients
        $mail->setFrom('joshuapastorpide10@gmail.com', 'RFID GPMS Admin');
        $mail->addAddress($email, $username);
        
        // Create reset link
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $domain = $_SERVER['HTTP_HOST'];
        $reset_link = "$protocol://$domain/admin/reset_password.php?token=$token";
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - RFID GPMS Admin';
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4e73df; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fc; }
                .button { background: #4e73df; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
                .footer { padding: 20px; text-align: center; color: #6c757d; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>RFID GPMS Admin Portal</h2>
                </div>
                <div class='content'>
                    <h3>Hello $username,</h3>
                    <p>You have requested to reset your password for the RFID GPMS Admin Portal.</p>
                    <p>Click the button below to reset your password. This link will expire in 1 hour.</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='$reset_link' class='button'>Reset Your Password</a>
                    </p>
                    <p>If the button doesn't work, copy and paste this link in your browser:</p>
                    <p><code style='background: #f1f1f1; padding: 10px; display: block; word-break: break-all;'>$reset_link</code></p>
                    <p>If you didn't request this password reset, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->AltBody = "Hello $username,\n\nYou have requested to reset your password for the RFID GPMS Admin Portal.\n\nPlease use this link to reset your password: $reset_link\n\nThis link will expire in 1 hour.\n\nIf you didn't request this, please ignore this email.";
        
        $mail->send();
        error_log("Password reset email sent successfully to: $email");
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - RFID GPMS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #e1e7f0, #b0caf0);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Heebo', sans-serif;
            padding: 20px;
        }
        .reset-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            max-width: 450px;
            width: 100%;
        }
        .reset-header {
            background: linear-gradient(135deg, #4e73df, #b0caf0);
            padding: 25px;
            text-align: center;
            color: white;
        }
        .reset-body {
            padding: 30px;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h3><i class="fas fa-key me-2"></i>RESET PASSWORD</h3>
            <p class="mb-0">Enter your email to reset your password</p>
        </div>
        
        <div class="reset-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="resetForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['forgot_csrf_token']; ?>">
                
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" class="form-control" name="email" placeholder="Enter your registered email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="captcha-container text-center p-3 bg-light rounded mb-3">
                    <label class="form-label mb-3"><i class="fas fa-shield-alt"></i> Security Verification</label>
                    <img src="captcha.php?<?php echo time(); ?>" alt="CAPTCHA" class="captcha-image d-block mx-auto mb-2" id="captchaImage">
                    <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="refreshCaptcha()">
                        <i class="fas fa-redo"></i> Refresh CAPTCHA
                    </button>
                    <input type="text" class="form-control" name="captcha" placeholder="Enter CAPTCHA code" required maxlength="6">
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                    <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                </button>
            </form>

            <div class="text-center">
                <a href="index.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        function refreshCaptcha() {
            const captchaImage = document.getElementById('captchaImage');
            captchaImage.src = 'captcha.php?' + new Date().getTime();
            document.querySelector('input[name="captcha"]').value = '';
        }

        document.getElementById('resetForm').addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            btn.disabled = true;
        });
    </script>
</body>
</html>