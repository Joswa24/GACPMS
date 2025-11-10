<?php


// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gpassdb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['login'])) {
    $username = $_POST['user'];
    $password = $_POST['password'];
    
    // Prevent SQL injection
    $username = stripslashes($username);
    $password = stripslashes($password);
    $username = $conn->real_escape_string($user);
    $password = $conn->real_escape_string($password);
    
    // Query to check user credentials
    $sql = "SELECT * FROM users WHERE user='$user' AND password='$password'";
    $result = $conn->query($sql);
    
   // After successful login in authenticate.php
if (password_verify($password, $user['password'])) {
    $_SESSION['user'] = $user;
    header("Location: dashboard.php"); // This is the key line
    exit();

    } else {
        // Login failed
        echo "<script>alert('Invalid username or password'); window.location.href = 'index.php';</script>";
        exit();
    }
}

$conn->close();
?>