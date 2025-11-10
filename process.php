<?php
// process.php

// 1. Get form data
$name = $_POST['name'];
$email = $_POST['email'];
$message = $_POST['message'];
$recaptcha_response = $_POST['recaptcha_response'];

// 2. Verify reCAPTCHA
$secret_key = '6Ld2w-QrAAAAAFeIvhKm5V6YBpIsiyHIyzHxeqm-';
$url = 'https://www.google.com/recaptcha/api/siteverify';
$data = [
    'secret' => $secret_key,
    'response' => $recaptcha_response
];

// Send verification request to Google
$options = [
    'http' => [
        'method' => 'POST',
        'content' => http_build_query($data)
    ]
];
$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$response = json_decode($result);

// 3. Check if reCAPTCHA passed
if ($response->success && $response->score >= 0.5) {
    // reCAPTCHA successful - process the form
    // Save to database, send email, etc.
    echo "Thank you for your message!";
} else {
    // reCAPTCHA failed
    echo "Security verification failed. Please try again.";
}
?>