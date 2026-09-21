<?php
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function requireUserLogin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_logged_in'])) {
        header("Location: " . BASE_URL . "user/login.php"); exit();
    }
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset(); session_destroy();
        header("Location: " . BASE_URL . "user/login.php?msg=timeout"); exit();
    }
    $_SESSION['last_activity'] = time();
}

function requireAdminLogin() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_logged_in'])) {
        header("Location: " . BASE_URL . "admin/login.php"); exit();
    }
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset(); session_destroy();
        header("Location: " . BASE_URL . "admin/login.php?msg=timeout"); exit();
    }
    $_SESSION['last_activity'] = time();
}

function redirectIfUserLoggedIn() {
    if (isset($_SESSION['user_logged_in'])) { header("Location: " . BASE_URL . "user/dashboard.php"); exit(); }
}
function redirectIfAdminLoggedIn() {
    if (isset($_SESSION['admin_logged_in'])) { header("Location: " . BASE_URL . "admin/dashboard.php"); exit(); }
}
