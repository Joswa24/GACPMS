<?php
// test_email.php
echo "<h2>Email Diagnostic Test</h2>";

// Test PHPMailer installation
try {
    require_once 'PHPMailer/src/PHPMailer.php';
    require_once 'PHPMailer/src/SMTP.php';
    require_once 'PHPMailer/src/Exception.php';
    echo "✓ PHPMailer loaded successfully<br>";
} catch (Exception $e) {
    echo "✗ PHPMailer loading failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test email function
function testEmailSending() {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Enable verbose debug output
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            echo "Debug: $str<br>";
        };
        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'joshuapastorpide10@gmail.com';
        $mail->Password = 'bmnvognbjqcpxcyf';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 10;
        
        $mail->setFrom('joshuapastorpide10@gmail.com', 'Test Sender');
        $mail->addAddress('joshuapastorpide10@gmail.com', 'Test Receiver');
        
        $mail->Subject = 'Test Email from RFID System';
        $mail->Body = 'This is a test email from your RFID system.';
        
        if ($mail->send()) {
            return "SUCCESS: Test email sent!";
        } else {
            return "FAILED: " . $mail->ErrorInfo;
        }
    } catch (Exception $e) {
        return "EXCEPTION: " . $e->getMessage();
    }
}

echo "<h3>Test Results:</h3>";
echo testEmailSending();
?>