<?php
session_start();
header('Content-Type: image/png');

// Generate a random CAPTCHA code
$captcha_code = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789"), 0, 6);

// Store in session - use the same variable name as forgot_password.php
$_SESSION['captcha'] = $captcha_code; // Changed from 'captcha_code' to 'captcha'

// Create an image (150x50 pixels)
$image = imagecreate(150, 50);

// Colors
$bg_color = imagecolorallocate($image, 248, 249, 252); // Light background
$text_color = imagecolorallocate($image, 78, 115, 223); // Primary color
$line_color = imagecolorallocate($image, 176, 202, 240); // Secondary color
$noise_color = imagecolorallocate($image, 225, 231, 240); // Very light color

// Fill background
imagefill($image, 0, 0, $bg_color);

// Add noise to the image (random pixels)
for ($i = 0; $i < 1000; $i++) {
    imagesetpixel($image, rand(0, 150), rand(0, 50), $noise_color);
}

// Add noise (random lines)
for ($i = 0; $i < 5; $i++) {
    imageline($image, rand(0, 150), rand(0, 50), rand(0, 150), rand(0, 50), $line_color);
}

// Font file path - Make sure this points to a valid .ttf font file on your server
$font_path = __DIR__ . '/fonts/CourierPrime-Bold.ttf';  // Change this to the actual path of your font file

// Check if the font exists, if not use built-in font
if (file_exists($font_path)) {
    // Add the CAPTCHA code to the image using TrueType font
    $font_size = 20;
    $angle = 0;
    $x = 20;
    $y = 35;
    imagettftext($image, $font_size, $angle, $x, $y, $text_color, $font_path, $captcha_code);
} else {
    // Fallback to built-in font if TrueType font not available
    $font_size = 5;
    $x = 20;
    $y = 20;
    imagestring($image, $font_size, $x, $y, $captcha_code, $text_color);
}

// Output the image as PNG
imagepng($image);

// Clean up
imagedestroy($image);
?>