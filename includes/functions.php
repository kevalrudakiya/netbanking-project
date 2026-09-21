<?php
function sanitize($data) { return htmlspecialchars(strip_tags(trim($data))); }
function generateAccountNumber() { return '100' . str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT); }
function generateReferenceNo() { return 'TXN' . date('Ymd') . strtoupper(substr(uniqid(), -6)); }
function formatCurrency($amount) { return '₹' . number_format($amount, 2); }
function hashPassword($plain) { return password_hash($plain, PASSWORD_BCRYPT); }
function verifyPassword($plain, $hash) { return password_verify($plain, $hash); }
function setFlash($type, $message) { $_SESSION['flash'] = ['type' => $type, 'message' => $message]; }
function getFlash() {
    if (isset($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); return $flash; }
    return null;
}
function showFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = $flash['type']; $msg = htmlspecialchars($flash['message']);
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>{$msg}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}
function isLockedOut($conn, $username, $ip) {
    $window = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as attempts FROM login_attempts WHERE username=? AND ip_address=? AND attempted_at>?");
    mysqli_stmt_bind_param($stmt, 'sss', $username, $ip, $window);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $row['attempts'] >= MAX_LOGIN_ATTEMPTS;
}
function logFailedAttempt($conn, $username, $ip) {
    $stmt = mysqli_prepare($conn, "INSERT INTO login_attempts (username, ip_address) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, 'ss', $username, $ip);
    mysqli_stmt_execute($stmt);
    
    // Also log to unified audit logs
    logAudit($conn, 'user', null, 'user_login_failed', "Failed login attempt for username: $username");
}
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
    return $_SERVER['REMOTE_ADDR'];
}

/**
 * Centralized Account Status Validation
 * Fetches user account and enforces status rules. Redirects if rule broken.
 */
function validateAccountStatus($conn, $user_id, $operation_type = 'financial') {
    $stmt = mysqli_prepare($conn, "SELECT a.*, u.status as user_status FROM accounts a JOIN users u ON a.user_id = u.user_id WHERE a.user_id=?");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $account = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($account && $account['user_status'] === 'blocked') {
        session_unset(); session_destroy();
        header("Location: login.php?msg=blocked");
        exit;
    }

    $is_dashboard = (basename($_SERVER['PHP_SELF']) === 'dashboard.php');

    if (!$account) {
        if ($is_dashboard) return null;
        setFlash('danger', 'No account found. Please open an account first.');
        header("Location: dashboard.php");
        exit;
    }

    if ($account['status'] === 'closed') {
        if ($operation_type === 'financial' || $operation_type === 'settings') {
            setFlash('danger', 'Your account is closed. Operations are blocked.');
            if (!$is_dashboard) {
                header("Location: dashboard.php");
                exit;
            }
        }
    } elseif ($account['status'] === 'frozen') {
        if ($operation_type === 'financial') {
            setFlash('danger', 'Your account is frozen. Financial transactions are blocked.');
            if (!$is_dashboard) {
                header("Location: dashboard.php");
                exit;
            }
        }
    }

    return $account;
}

/**
 * Validates the receiver account for a transfer operation
 */
function validateReceiverForTransfer($conn, $account_number) {
    $stmt = mysqli_prepare($conn, "SELECT a.account_id, a.status, u.user_id, u.full_name as user_name FROM accounts a JOIN users u ON a.user_id = u.user_id WHERE a.account_number=?");
    mysqli_stmt_bind_param($stmt, 's', $account_number);
    mysqli_stmt_execute($stmt);
    $receiver = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$receiver) {
        return ['error' => 'Receiver account does not exist.'];
    }
    if ($receiver['status'] !== 'active') {
        return ['error' => 'Receiver account is not active and cannot receive funds.'];
    }
    return [
        'account_id' => $receiver['account_id'],
        'user_id' => $receiver['user_id'],
        'user_name' => $receiver['user_name']
    ];
}

/**
 * CSRF Protection Helpers
 */
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function regenerateCsrfToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Complete Audit Log Helper
 */
function logAudit($conn, $actor_type, $actor_id, $action, $details = null, $related_account_id = null, $related_transaction_id = null) {
    $ip = getClientIP();
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    
    $stmt = mysqli_prepare($conn, "
        INSERT INTO audit_logs (actor_type, actor_id, action, details, ip_address, user_agent, related_account_id, related_transaction_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if (!$stmt) return false;
    
    $details = $details !== '' ? $details : null;
    $related_account_id = $related_account_id ? (int)$related_account_id : null;
    $related_transaction_id = $related_transaction_id ? (int)$related_transaction_id : null;
    
    mysqli_stmt_bind_param($stmt, 'sissssii', $actor_type, $actor_id, $action, $details, $ip, $ua, $related_account_id, $related_transaction_id);
    mysqli_stmt_execute($stmt);
}

function sendNotification($conn, $user_id, $title, $message, $type = 'user') {
    $stmt = mysqli_prepare($conn, "INSERT INTO notifications (type, user_id, title, message) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'siss', $type, $user_id, $title, $message);
    mysqli_stmt_execute($stmt);
}

function getUnreadNotificationCount($conn, $user_id, $type = 'user') {
    // If user_id = 0 it's a broadcast to all users of that type.
    // For counting unread, we usually only care about notifications specifically for this user or broadcasts they haven't read.
    // Wait, tracking read state for broadcasts per-user requires a separate mapping table.
    // Since we don't have a mapping table, we'll assume broadcasts (user_id=0) are shown but maybe not counted in unread badge perfectly, or we count them if we can't mark them read.
    // Actually, marking a broadcast (user_id=0) as read would mark it read for EVERYONE if we update the row!
    // To keep it simple and robust given the schema: user_id=0 broadcasts will be fetched, but maybe we don't allow them to be marked read, or we just count all unread for the specific user + broadcasts.
    // Let's just count user specific + broadcast.
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM notifications WHERE type=? AND (user_id=? OR user_id=0) AND is_read=0");
    mysqli_stmt_bind_param($stmt, 'si', $type, $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $res['c'] ?? 0;
}

/**
 * Sanitize a field to prevent CSV Formula Injection in Excel
 */
function sanitizeCsvField($field) {
    if ($field === null) return '';
    $field = (string)$field;
    if (in_array(substr($field, 0, 1), ['=', '+', '-', '@', "\t", "\r"])) {
        return "'" . $field;
    }
    return $field;
}

/**
 * Stream a MySQLi result as a CSV file to the browser
 */
function exportToCsv($filename, $result, $excludeColumns = ['password', 'pin', 'csrf_token']) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $out = fopen('php://output', 'w');
    
    $headersPrinted = false;
    while ($row = mysqli_fetch_assoc($result)) {
        // Remove sensitive columns
        foreach ($excludeColumns as $col) {
            unset($row[$col]);
        }
        
        if (!$headersPrinted) {
            fputcsv($out, array_keys($row));
            $headersPrinted = true;
        }
        
        // Sanitize for formula injection
        $safeRow = array_map('sanitizeCsvField', array_values($row));
        fputcsv($out, $safeRow);
    }
    
    if (!$headersPrinted) {
        // If empty result set, output something so it's not totally blank
        fputcsv($out, ['No data found']);
    }
    
    fclose($out);
    exit();
}
