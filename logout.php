<?php
session_start();
include 'connection.php';

// Just destroy the session - no database changes
session_destroy();

// Redirect to login page
header("Location: index.php");
exit();
?>