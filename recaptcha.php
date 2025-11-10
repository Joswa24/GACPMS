<?php
class reCAPTCHA {
    private $secret_key;
    private $site_key;
    
    public function __construct($secret_key, $site_key) {
        $this->secret_key = $secret_key;
        $this->site_key = $site_key;
    }
    
    public function verify($recaptcha_response, $threshold = 0.5) {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $this->secret_key,
            'response' => $recaptcha_response
        ];
        
        $options = [
            'http' => [
                'method' => 'POST',
                'content' => http_build_query($data),
                'header' => 'Content-type: application/x-www-form-urlencoded'
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $response = json_decode($result);
        
        return [
            'success' => $response->success ?? false,
            'score' => $response->score ?? 0,
            'passed' => ($response->success && $response->score >= $threshold)
        ];
    }
    
    public function getSiteKey() {
        return $this->site_key;
    }
}

// Usage
$recaptcha = new reCAPTCHA('6Ld2w-QrAAAAAFeIvhKm5V6YBpIsiyHIyzHxeqm-', '6Ld2w-QrAAAAAKcWH94dgQumTQ6nQ3EiyQKHUw4_');

if ($_POST) {
    $verification = $recaptcha->verify($_POST['recaptcha_response']);
    
    if ($verification['passed']) {
        // Process form
        echo "Success! Score: " . $verification['score'];
    } else {
        echo "Failed! Score: " . $verification['score'];
    }
}
?>