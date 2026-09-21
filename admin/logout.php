<?php
require_once '../config/session.php';
require_once '../includes/functions.php';

if (isset($_SESSION['admin_id'])) {
    logAudit($conn, 'admin', $_SESSION['admin_id'], 'admin_logout', 'Admin logged out');
}

session_unset();
session_destroy();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
header("Location: " . BASE_URL . "admin/login.php");
exit();
