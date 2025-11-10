<?php
session_start();
if (isset($_POST['message'])) {
    $_SESSION['scanner_error'] = $_POST['message'];
}
?>