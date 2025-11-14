<?php
// Security Headers Configuration for RFID-GPMS
function setSecurityHeaders() {
    // Remove PHP version header
    header_remove('X-Powered-By');
    
    // Content Security Policy - Updated for reCAPTCHA v3
    $csp = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.google.com https://www.gstatic.com https://recaptcha.google.net https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://ajax.googleapis.com https://fonts.googleapis.com",
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com",
        "font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.gstatic.com",
        "img-src 'self' data: https:",
        "connect-src 'self' https://www.google.com https://www.gstatic.com https://recaptcha.google.net",
        "frame-src 'self' https://www.google.com https://recaptcha.google.net",
        "frame-ancestors 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "object-src 'none'",
        "media-src 'self'",
        "worker-src 'self' blob:",
        "manifest-src 'self'"
    ];
    
    header("Content-Security-Policy: " . implode("; ", $csp));
    
    // Other security headers
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // HSTS (only enable if you have HTTPS properly configured)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    }
    
    // Permissions Policy
    header('Permissions-Policy: geolocation=(self), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()');
    
    // Cache control for dynamic pages
    if (in_array(pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_EXTENSION), ['php', 'html'])) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

// Call this function at the beginning of every PHP file
setSecurityHeaders();
?>