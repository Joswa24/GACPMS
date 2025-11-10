
<?php
// Database credentials from Hostinger
$host = "127.0.0.1"; // Example: "mysql.hostinger.com"
$username = "u8027114156_rfidgpmsPass";
$password = "1rfidUser2025";
$database = "u8027114156_rfidgpms";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE TABLE IF NOT EXISTS consumers1 (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(255) NOT NULL,
    lastname VARCHAR(255) NOT NULL,
    middlename VARCHAR(50) DEFAULT NULL,
    brgy VARCHAR(50) DEFAULT NULL,
    city VARCHAR(50) DEFAULT NULL,
    telno VARCHAR(15) NOT NULL,
    email VARCHAR(255) NOT NULL,
    type ENUM('Institution','Commercial','Residential') NOT NULL,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    registration_date TIMESTAMP NOT NULL DEFAULT current_timestamp(),
    image_path VARCHAR(255) DEFAULT 'default.jpg',
    profile_picture VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'consumers1' created successfully.";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
