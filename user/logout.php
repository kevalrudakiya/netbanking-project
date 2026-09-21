<?php
require_once '../config/session.php';
require_once '../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    logAudit($conn, 'user', $_SESSION['user_id'], 'user_logout', 'User logged out');
}

session_unset();
session_destroy();
header("Location: " . BASE_URL . "user/login.php");
exit();
