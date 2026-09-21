<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
redirectIfAdminLoggedIn();



$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid CSRF token. Please try again.";
    } else {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            $error = "Please enter username and password.";
        } else {
            $stmt = mysqli_prepare($conn, "SELECT admin_id, full_name, password, failed_attempts, locked_until FROM admins WHERE username=?");
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            $ip_address = $_SERVER['REMOTE_ADDR'];

            if ($admin) {
                $is_locked = false;
                if ($admin['locked_until'] !== null) {
                    $locked_time = strtotime($admin['locked_until']);
                    if ($locked_time > time()) {
                        $is_locked = true;
                        $error = "Account temporarily locked due to too many failed attempts. Please try again later.";
                    } else {
                        // Lock expired, reset
                        $reset_stmt = mysqli_prepare($conn, "UPDATE admins SET failed_attempts=0, locked_until=NULL WHERE admin_id=?");
                        mysqli_stmt_bind_param($reset_stmt, 'i', $admin['admin_id']);
                        mysqli_stmt_execute($reset_stmt);
                        $admin['failed_attempts'] = 0;
                        $admin['locked_until'] = null;
                    }
                }

                if (!$is_locked) {
                    if (verifyPassword($password, $admin['password'])) {
                        // Success
                        session_regenerate_id(true);
                        regenerateCsrfToken();
                        $upd = mysqli_prepare($conn, "UPDATE admins SET failed_attempts=0, locked_until=NULL, last_login=NOW() WHERE admin_id=?");
                        mysqli_stmt_bind_param($upd, 'i', $admin['admin_id']);
                        mysqli_stmt_execute($upd);

                        logAudit($conn, 'admin', $admin['admin_id'], 'admin_login_success', 'Admin logged in successfully');

                        $_SESSION['admin_id']        = $admin['admin_id'];
                        $_SESSION['admin_name']      = $admin['full_name'];
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['last_activity']   = time();
                        header("Location: dashboard.php"); exit();
                    } else {
                        // Incorrect password
                        $attempts = $admin['failed_attempts'] + 1;
                        $locked_until = null;
                        if ($attempts >= 5) {
                            $locked_until = date('Y-m-d H:i:s', time() + 900); // 15 mins
                        }
                        
                        $upd = mysqli_prepare($conn, "UPDATE admins SET failed_attempts=?, locked_until=? WHERE admin_id=?");
                        mysqli_stmt_bind_param($upd, 'isi', $attempts, $locked_until, $admin['admin_id']);
                        mysqli_stmt_execute($upd);

                        $log = mysqli_prepare($conn, "INSERT INTO audit_logs (actor_type, actor_id, action, details, ip_address) VALUES ('admin', ?, 'admin_login_failed', 'Failed login attempt', ?)");
                        mysqli_stmt_bind_param($log, 'is', $admin['admin_id'], $ip_address);
                        mysqli_stmt_execute($log);

                        $error = "Invalid username or password.";
                    }
                }
            } else {
                // Admin not found
                $log = mysqli_prepare($conn, "INSERT INTO audit_logs (actor_type, action, details, ip_address) VALUES ('system', 'admin_login_failed', ?, ?)");
                $details = "Failed login attempt for unknown user: " . $username;
                mysqli_stmt_bind_param($log, 'ss', $details, $ip_address);
                mysqli_stmt_execute($log);

                $error = "Invalid username or password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('../assets/background.jpg');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-color: #030b1e;
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: rgba(2, 10, 28, 0.75);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 2px solid #0052d4;
            border-radius: 24px;
            box-shadow: 0 0 40px rgba(0, 140, 255, 0.4);
            padding: 45px 35px;
            width: 100%;
            max-width: 440px;
            margin: auto;
        }
        .bank-icon-wrapper {
            background: linear-gradient(135deg, #d4005e, #f74364);
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
            box-shadow: 0 0 20px rgba(247, 67, 100, 0.6);
        }
        .bank-icon { font-size: 2.2rem; color: #ffffff; }
        .brand-title { font-size: 2.2rem; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 6px; }
        .brand-title span { color: #4364f7; }
        .portal-subtitle {
            color: #8da2bb;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .portal-subtitle::before, .portal-subtitle::after {
            content: '';
            width: 25px;
            height: 1px;
            background-color: rgba(0, 140, 255, 0.4);
        }
        .form-label { color: #ffffff; font-size: 0.95rem; font-weight: 500; margin-bottom: 8px; }
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-wrapper .input-icon { position: absolute; left: 16px; color: #8da2bb; font-size: 1.1rem; pointer-events: none; }
        .form-control {
            background-color: rgba(5, 19, 48, 0.6) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 10px !important;
            color: #ffffff !important;
            padding: 14px 16px 14px 45px !important;
            font-size: 1rem;
        }
        .form-control::placeholder { color: #4b617c; }
        .form-control:focus { border-color: #f74364 !important; box-shadow: 0 0 12px rgba(247, 67, 100, 0.5) !important; }
        .btn-toggle-visibility { position: absolute; right: 16px; background: transparent; border: none; color: #8da2bb; padding: 0; z-index: 5; }
        .btn-toggle-visibility:hover { color: #ffffff; }
        .btn-login {
            background: linear-gradient(90deg, #d4005e, #f74364);
            border: none !important;
            border-radius: 10px !important;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 14px !important;
            color: white;
            box-shadow: 0 5px 20px rgba(247, 67, 100, 0.4);
            transition: all 0.3s ease;
        }
        .btn-login:hover { opacity: 0.95; box-shadow: 0 5px 25px rgba(247, 67, 100, 0.6); transform: translateY(-1px); }
        .btn-portal-link {
            display: flex; align-items: center; justify-content: center; width: 100%;
            padding: 12px; margin-top: 12px; background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.15) !important; border-radius: 10px;
            color: #ffffff; text-decoration: none; font-size: 0.95rem; font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn-portal-link i { margin-right: 10px; color: #8da2bb; font-size: 1.05rem; }
        .btn-portal-link:hover { background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 255, 255, 0.4) !important; color: #ffffff; }
        .alert { background-color: rgba(220, 53, 69, 0.2); border: 1px solid rgba(220, 53, 69, 0.4); color: #ff858d; border-radius: 10px; }
    </style>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center">
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="bank-icon-wrapper">
                <i class="fas fa-shield-halved bank-icon"></i>
            </div>
            <h3 class="brand-title">Secure<span>Bank</span></h3>
            <p class="portal-subtitle">Admin Login Portal</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small mb-3"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-user-shield input-icon"></i>
                    <input type="text" name="username" class="form-control" placeholder="Enter admin username" required autocomplete="off">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required autocomplete="new-password">
                    <button class="btn-toggle-visibility" type="button" onclick="togglePass()">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </div>
        </form>

        <div class="mt-4">
            <a href="../user/login.php" class="btn-portal-link"><i class="fas fa-arrow-left"></i>Back to User Login</a>
        </div>

        <div class="text-center mt-4 pt-2" style="font-size: 12px; color: #64748b;">
            <i class="fas fa-shield-alt me-1"></i> © 2026 SecureBank. All rights reserved.
            <div style="color: #f74364; margin-top: 3px; font-weight: 500;">Restricted access — administrators only</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass() {
    const p = document.getElementById('password');
    const i = document.getElementById('eyeIcon');
    if (p.type === 'password') { p.type = 'text'; i.className = 'fas fa-eye-slash'; }
    else { p.type = 'password'; i.className = 'fas fa-eye'; }
}
</script>
</body>
</html>
