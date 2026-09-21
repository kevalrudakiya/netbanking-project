<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'netbanking');
define('APP_NAME', 'SecureBank');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/netbanking/');
define('SESSION_TIMEOUT', 1800);
define('MAX_LOGIN_ATTEMPTS', 5);

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Unable to connect to the database. Please try again later.");
}
mysqli_set_charset($conn, 'utf8mb4');
