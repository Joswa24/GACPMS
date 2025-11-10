<?php
// test-smtp.php
 $host = 'smtp.gmail.com';
 $ports = [587, 465];

foreach ($ports as $port) {
    $timeout = 5; // seconds
    echo "Testing connection to $host on port $port... ";
    
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    
    if ($socket) {
        echo "<span style='color:green;'>SUCCESS</span><br>";
        fclose($socket);
    } else {
        echo "<span style='color:red;'>FAILED</span> - Error: $errno - $errstr<br>";
    }
}
?>