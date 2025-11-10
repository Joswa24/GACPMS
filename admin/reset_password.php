<?php
// reset_password.php

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../connection.php';

$error = '';
$success = '';
$valid_token = false;
$user_id = null;

// Get token from multiple sources (URL, session, POST)
$token = '';

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = trim($_GET['token']);
} elseif (isset($_SESSION['temp_reset_token']) && !empty($_SESSION['temp_reset_token'])) {
    $token = $_SESSION['temp_reset_token'];
} elseif (isset($_POST['token']) && !empty($_POST['token'])) {
    $token = trim($_POST['token']);
}

// Debug: Check what token we received
error_log("Reset Password - Token received: " . ($token ? $token : 'EMPTY'));
class PasswordStrengthValidator {
    
    /**
     * Check password strength and return detailed analysis
     * 
     * @param string $password The password to evaluate
     * @return array Detailed strength analysis
     */
    public static function checkPasswordStrength($password) {
        $score = 0;
        $maxScore = 100;
        $feedback = [];
        $requirements = [];
        
        // Length check
        $length = strlen($password);
        if ($length >= 12) {
            $score += 25;
            $feedback[] = "✓ Good length (12+ characters)";
        } elseif ($length >= 8) {
            $score += 15;
            $feedback[] = "✓ Minimum length met (8+ characters)";
        } else {
            $feedback[] = "✗ Password too short (minimum 8 characters required)";
            $requirements[] = "at least 8 characters";
        }
        
        // Uppercase letters check
        if (preg_match('/[A-Z]/', $password)) {
            $score += 20;
            $uppercaseCount = preg_match_all('/[A-Z]/', $password);
            $feedback[] = "✓ Contains uppercase letters ($uppercaseCount found)";
        } else {
            $feedback[] = "✗ Missing uppercase letters (A-Z)";
            $requirements[] = "uppercase letters (A-Z)";
        }
        
        // Lowercase letters check
        if (preg_match('/[a-z]/', $password)) {
            $score += 15;
            $lowercaseCount = preg_match_all('/[a-z]/', $password);
            $feedback[] = "✓ Contains lowercase letters ($lowercaseCount found)";
        } else {
            $feedback[] = "✗ Missing lowercase letters (a-z)";
            $requirements[] = "lowercase letters (a-z)";
        }
        
        // Numbers check
        if (preg_match('/[0-9]/', $password)) {
            $score += 20;
            $numberCount = preg_match_all('/[0-9]/', $password);
            $feedback[] = "✓ Contains numbers ($numberCount found)";
        } else {
            $feedback[] = "✗ Missing numbers (0-9)";
            $requirements[] = "numbers (0-9)";
        }
        
        // Special characters check
        if (preg_match('/[!@#$%^&*(),.?":{}|<>~\[\]_+\-=\\\/]/', $password)) {
            $score += 20;
            $specialCount = preg_match_all('/[!@#$%^&*(),.?":{}|<>~\[\]_+\-=\\\/]/', $password);
            $feedback[] = "✓ Contains special characters ($specialCount found)";
        } else {
            $feedback[] = "✗ Missing special characters (!@#$% etc.)";
            $requirements[] = "special characters (!@#$%^&* etc.)";
        }
        
        // Determine strength level
        $strengthLevel = self::getStrengthLevel($score);
        $colorClass = self::getStrengthColor($strengthLevel);
        
        return [
            'score' => $score,
            'max_score' => $maxScore,
            'percentage' => round(($score / $maxScore) * 100),
            'strength_level' => $strengthLevel,
            'color_class' => $colorClass,
            'feedback' => $feedback,
            'requirements_missing' => $requirements,
            'is_acceptable' => $score >= 60, // Minimum acceptable score
            'is_strong' => $score >= 80
        ];
    }
    
    /**
     * Get strength level based on score
     */
    private static function getStrengthLevel($score) {
        if ($score >= 90) return 'Very Strong';
        if ($score >= 80) return 'Strong';
        if ($score >= 70) return 'Good';
        if ($score >= 60) return 'Fair';
        if ($score >= 40) return 'Weak';
        return 'Very Weak';
    }
    
    /**
     * Get CSS color class based on strength level
     */
    private static function getStrengthColor($strengthLevel) {
        switch ($strengthLevel) {
            case 'Very Strong': return 'strength-very-strong';
            case 'Strong': return 'strength-strong';
            case 'Good': return 'strength-good';
            case 'Fair': return 'strength-fair';
            case 'Weak': return 'strength-weak';
            default: return 'strength-very-weak';
        }
    }
    
    /**
     * Quick validation for minimum requirements
     */
    public static function validatePassword($password, $minLength = 8) {
        if (strlen($password) < $minLength) {
            return false;
        }
        
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasLower = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[!@#$%^&*(),.?":{}|<>~\[\]_+\-=\\\/]/', $password);
        
        // Require at least 3 out of 4 character types
        $characterTypes = [$hasUpper, $hasLower, $hasNumber, $hasSpecial];
        $typeCount = count(array_filter($characterTypes));
        
        return $typeCount >= 3;
    }
}

/**
 * Display password strength meter (HTML output)
 */
function displayPasswordStrengthMeter($password) {
    $strength = PasswordStrengthValidator::checkPasswordStrength($password);
    
    $html = '<div class="password-strength-meter">';
    $html .= '<div class="strength-header">';
    $html .= '<span>Password Strength: </span>';
    $html .= '<strong class="' . $strength['color_class'] . '">' . $strength['strength_level'] . '</strong>';
    $html .= '<span> (' . $strength['percentage'] . '%)</span>';
    $html .= '</div>';
    
    // Progress bar
    $html .= '<div class="progress" style="height: 8px; margin: 10px 0;">';
    $html .= '<div class="progress-bar ' . $strength['color_class'] . '" role="progressbar" ';
    $html .= 'style="width: ' . $strength['percentage'] . '%;" ';
    $html .= 'aria-valuenow="' . $strength['percentage'] . '" aria-valuemin="0" aria-valuemax="100"></div>';
    $html .= '</div>';
    
    // Feedback
    $html .= '<div class="strength-feedback">';
    foreach ($strength['feedback'] as $item) {
        $html .= '<div class="feedback-item">' . htmlspecialchars($item) . '</div>';
    }
    $html .= '</div>';
    
    // Requirements (if any missing)
    if (!empty($strength['requirements_missing'])) {
        $html .= '<div class="requirements-missing">';
        $html .= '<strong>Missing requirements:</strong> ';
        $html .= implode(', ', $strength['requirements_missing']);
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}
// Validate token
if (!empty($token)) {
    try {
        // Check if token exists and is not expired
        $stmt = $db->prepare("
            SELECT prt.user_id, prt.expires_at, u.email, u.username 
            FROM password_reset_tokens prt 
            JOIN user u ON prt.user_id = u.id 
            WHERE prt.token = ? AND prt.used = 0 AND prt.expires_at > NOW()
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $token_data = $result->fetch_assoc();
            $user_id = $token_data['user_id'];
            $valid_token = true;
            
            // Store in session for the form submission
            $_SESSION['reset_user_id'] = $user_id;
            $_SESSION['reset_token'] = $token;
            $_SESSION['reset_email'] = $token_data['email'];
            
            error_log("Reset Password - Valid token for user: " . $token_data['email']);
        } else {
            $error = "Invalid or expired reset token. Please request a new password reset.";
            error_log("Reset Password - Invalid token: " . $token);
            
            // Clean up
            unset($_SESSION['temp_reset_token']);
            unset($_SESSION['reset_token']);
        }
    } catch (Exception $e) {
        error_log("Token validation error: " . $e->getMessage());
        $error = "Database error. Please try again.";
    }
} else {
    $error = "No reset token provided. Please check your email for a valid reset link or request a new one.";
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash new password and update user record
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("UPDATE user SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                // Mark token as used
                $stmt = $db->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                
                // Clear all session data
                unset($_SESSION['temp_reset_token']);
                unset($_SESSION['reset_token']);
                unset($_SESSION['reset_user_id']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_token_sent']);
                
                $success = "Password reset successfully! You can now login with your new password.";
                $valid_token = false; // Token is now used
                
                error_log("Password reset successful for user ID: " . $user_id);
            } else {
                $error = "Failed to reset password. Please try again.";
            }
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            $error = "Database error. Please try again.";
        }
    }
}

// If we have a temporary token from forgot_password.php, clear it after use
if (isset($_SESSION['temp_reset_token']) && $valid_token) {
    unset($_SESSION['temp_reset_token']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - RFID GPMS Admin</title>
    
    <!-- Security Meta Tags -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data: https:;">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap">
    
    <style>
        :root {
            --primary-color: #e1e7f0ff;
            --secondary-color: #b0caf0ff;
            --accent-color: #4e73df;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Heebo', sans-serif;
            padding: 20px;
        }
        
        .reset-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        
        .reset-header {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            padding: 25px;
            text-align: center;
            color: white;
        }
        
        .reset-header h3 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
        }
        
        .reset-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .input-group {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .input-group-text {
            background-color: var(--light-bg);
            border: none;
            padding: 0.75rem 1rem;
            color: var(--accent-color);
        }
        
        .form-control {
            border: none;
            padding: 0.75rem 1rem;
            background-color: var(--light-bg);
        }
        
        .password-toggle {
            background-color: var(--light-bg);
            border: none;
            color: var(--accent-color);
            cursor: pointer;
            padding: 0.75rem 1rem;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: var(--secondary-color);
        }
        
        .btn-reset {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 115, 223, 0.4);
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 0.875rem;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #fd7e14; }
        .strength-strong { color: #198754; }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .password-strength-meter {
            margin: 15px 0;
            padding: 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .strength-header {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .progress {
            background-color: #e9ecef;
            border-radius: 4px;
        }
        
        .progress-bar {
            transition: width 0.3s ease;
        }
        
        .strength-feedback {
            font-size: 0.875rem;
            margin-top: 10px;
        }
        
        .feedback-item {
            margin: 2px 0;
        }
        
        .requirements-missing {
            margin-top: 10px;
            padding: 10px;
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            font-size: 0.875rem;
            color: #856404;
        }
        
        /* Strength color classes */
        .strength-very-strong { color: #198754; background-color: #198754; }
        .strength-strong { color: #20c997; background-color: #20c997; }
        .strength-good { color: #0dcaf0; background-color: #0dcaf0; }
        .strength-fair { color: #ffc107; background-color: #ffc107; }
        .strength-weak { color: #fd7e14; background-color: #fd7e14; }
        .strength-very-weak { color: #dc3545; background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h3><i class="fas fa-key me-2"></i>SET NEW PASSWORD</h3>
            <p class="mb-0">Create your new password</p>
        </div>
        
        <div class="reset-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <?php if ($success): ?>
                        <div class="mt-2">
                            <a href="index.php" class="btn btn-sm btn-outline-success">Go to Login</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($valid_token): ?>
                <form method="POST" id="resetForm">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="new_password" class="form-label"><i class="fas fa-lock"></i> New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   placeholder="Enter new password (min. 8 characters)" required minlength="8">
                            <button type="button" class="password-toggle" id="toggleNewPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label"><i class="fas fa-lock"></i> Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm new password" required minlength="8">
                            <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-match" id="passwordMatch"></div>
                    </div>

                    <button type="submit" class="btn btn-reset mb-3">
                        <i class="fas fa-save me-2"></i>Reset Password
                    </button>
                </form>
            <?php elseif (empty($success) && empty($error)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    No reset token provided. Please check your email for a valid reset link or request a new one.
                </div>
            <?php endif; ?>

            <div class="back-link">
                <a href="index.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-2"></i>Back to Login
                </a>
                <?php if (!$valid_token && empty($success)): ?>
                    <br>
                    <a href="forgot_password.php" class="text-decoration-none mt-2 d-inline-block">
                        <i class="fas fa-redo me-2"></i>Request New Reset Link
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle functionality
        function setupPasswordToggle(passwordFieldId, toggleButtonId) {
            const passwordField = document.getElementById(passwordFieldId);
            const toggleButton = document.getElementById(toggleButtonId);
            const eyeIcon = toggleButton.querySelector('i');
            
            toggleButton.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                
                // Toggle eye icon
                if (type === 'text') {
                    eyeIcon.classList.remove('fa-eye');
                    eyeIcon.classList.add('fa-eye-slash');
                    toggleButton.setAttribute('title', 'Hide password');
                } else {
                    eyeIcon.classList.remove('fa-eye-slash');
                    eyeIcon.classList.add('fa-eye');
                    toggleButton.setAttribute('title', 'Show password');
                }
            });
        }

        // Initialize password toggles
        document.addEventListener('DOMContentLoaded', function() {
            setupPasswordToggle('new_password', 'toggleNewPassword');
            setupPasswordToggle('confirm_password', 'toggleConfirmPassword');
        });

        // Password strength indicator
        const passwordInput = document.getElementById('new_password');
        const strengthText = document.getElementById('passwordStrength');
        const confirmInput = document.getElementById('confirm_password');
        const matchText = document.getElementById('passwordMatch');

        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = '';
                let color = '';

                if (password.length === 0) {
                    strength = '';
                } else if (password.length < 8) {
                    strength = 'Weak - at least 8 characters required';
                    color = 'strength-weak';
                } else if (password.length < 12) {
                    strength = 'Medium';
                    color = 'strength-medium';
                } else {
                    // Check for complexity
                    const hasUpper = /[A-Z]/.test(password);
                    const hasLower = /[a-z]/.test(password);
                    const hasNumbers = /\d/.test(password);
                    const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                    
                    let score = 0;
                    if (hasUpper) score++;
                    if (hasLower) score++;
                    if (hasNumbers) score++;
                    if (hasSpecial) score++;
                    
                    if (score >= 3) {
                        strength = 'Strong';
                        color = 'strength-strong';
                    } else {
                        strength = 'Medium - add more character types';
                        color = 'strength-medium';
                    }
                }
                
                if (strengthText) {
                    strengthText.textContent = strength;
                    strengthText.className = 'password-strength ' + color;
                }
            });

            // Password match indicator
            confirmInput.addEventListener('input', function() {
                const password = passwordInput.value;
                const confirm = this.value;
                
                if (confirm.length === 0) {
                    matchText.textContent = '';
                } else if (password === confirm) {
                    matchText.textContent = 'Passwords match';
                    matchText.className = 'password-match text-success';
                } else {
                    matchText.textContent = 'Passwords do not match';
                    matchText.className = 'password-match text-danger';
                }
            });

            // Form submission handler
            document.getElementById('resetForm').addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Resetting...';
                btn.disabled = true;
            });
        }

        function checkPasswordStrengthRealTime(password) {
            let score = 0;
            let feedback = [];
            
            // Length
            if (password.length >= 12) {
                score += 25;
                feedback.push("✓ Good length (12+ characters)");
            } else if (password.length >= 8) {
                score += 15;
                feedback.push("✓ Minimum length met (8+ characters)");
            } else {
                feedback.push("✗ Password too short (minimum 8 characters required)");
            }
            
            // Uppercase
            if (/[A-Z]/.test(password)) {
                score += 20;
                const count = (password.match(/[A-Z]/g) || []).length;
                feedback.push(`✓ Contains uppercase letters (${count} found)`);
            } else {
                feedback.push("✗ Missing uppercase letters (A-Z)");
            }
            
            // Lowercase
            if (/[a-z]/.test(password)) {
                score += 15;
                const count = (password.match(/[a-z]/g) || []).length;
                feedback.push(`✓ Contains lowercase letters (${count} found)`);
            } else {
                feedback.push("✗ Missing lowercase letters (a-z)");
            }
            
            // Numbers
            if (/[0-9]/.test(password)) {
                score += 20;
                const count = (password.match(/[0-9]/g) || []).length;
                feedback.push(`✓ Contains numbers (${count} found)`);
            } else {
                feedback.push("✗ Missing numbers (0-9)");
            }
            
            // Special characters
            if (/[!@#$%^&*(),.?":{}|<>~\[\]_+\-=\\\/]/.test(password)) {
                score += 20;
                const count = (password.match(/[!@#$%^&*(),.?":{}|<>~\[\]_+\-=\\\/]/g) || []).length;
                feedback.push(`✓ Contains special characters (${count} found)`);
            } else {
                feedback.push("✗ Missing special characters (!@#$% etc.)");
            }
            
            return {
                score: score,
                percentage: Math.round((score / 100) * 100),
                feedback: feedback
            };
        }

        // Enhanced password strength meter with real-time feedback
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('new_password');
            if (passwordInput) {
                const meterContainer = document.createElement('div');
                passwordInput.parentNode.appendChild(meterContainer);
                
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    if (password.length === 0) {
                        meterContainer.innerHTML = '';
                        return;
                    }
                    
                    const strength = checkPasswordStrengthRealTime(password);
                    
                    let html = '<div class="strength-header">';
                    html += '<span>Password Strength: </span>';
                    html += '<span> (' + strength.percentage + '%)</span>';
                    html += '</div>';
                    
                    html += '<div class="progress" style="height: 8px; margin: 10px 0;">';
                    html += '<div class="progress-bar" role="progressbar" ';
                    html += 'style="width: ' + strength.percentage + '%; background-color: ';
                    html += strength.percentage >= 80 ? '#198754' : 
                            strength.percentage >= 60 ? '#ffc107' : 
                            strength.percentage >= 40 ? '#fd7e14' : '#dc3545';
                    html += '"></div></div>';
                    
                    html += '<div class="strength-feedback">';
                    strength.feedback.forEach(item => {
                        html += '<div class="feedback-item">' + item + '</div>';
                    });
                    html += '</div>';
                    
                    meterContainer.innerHTML = html;
                });
            }
        });
    </script>
</body>
</html>