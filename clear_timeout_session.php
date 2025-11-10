<?php
session_start();
unset($_SESSION['timeout_time']);
echo json_encode(['success' => true]);
?>