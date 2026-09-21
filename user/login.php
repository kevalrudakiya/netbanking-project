<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
redirectIfUserLoggedIn();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
    $ip       = getClientIP();

    if (empty($username) || empty($password)) {
        $error = "Please enter username and password.";
    } elseif (isLockedOut($conn, $username, $ip)) {
        $error = "Too many failed attempts. Please try again after 15 minutes.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id, full_name, password, status FROM users WHERE username=?");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);

        if ($user && verifyPassword($password, $user['password'])) {
            if ($user['status'] === 'blocked') {
                $error = "Your account has been blocked. Please contact support.";
            } else {
                // Login success
                regenerateCsrfToken();
                $_SESSION['user_id']       = $user['user_id'];
                $_SESSION['user_name']     = $user['full_name'];
                $_SESSION['user_logged_in']= true;
                $_SESSION['last_activity'] = time();
                
                $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
                $log_stmt = mysqli_prepare($conn, "INSERT INTO user_login_history (user_id, ip_address, user_agent, status) VALUES (?, ?, ?, 'success')");
                mysqli_stmt_bind_param($log_stmt, 'iss', $user['user_id'], $ip, $ua);
                mysqli_stmt_execute($log_stmt);
                
                logAudit($conn, 'user', $user['user_id'], 'user_login', 'User logged in successfully');
                sendNotification($conn, $user['user_id'], "New Login", "A new login was detected on your account from IP $ip.");
                header("Location: dashboard.php"); exit();
            }
        } else {
            if ($user) {
                $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
                $log_stmt = mysqli_prepare($conn, "INSERT INTO user_login_history (user_id, ip_address, user_agent, status) VALUES (?, ?, ?, 'failed')");
                mysqli_stmt_bind_param($log_stmt, 'iss', $user['user_id'], $ip, $ua);
                mysqli_stmt_execute($log_stmt);
            }
            logFailedAttempt($conn, $username, $ip);
            $error = "Invalid username or password.";
        }
        }
    }
}

$timeout_msg = isset($_GET['msg']) && $_GET['msg'] === 'timeout';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
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
            background: linear-gradient(135deg, #0052d4, #4364f7);
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
            box-shadow: 0 0 20px rgba(67, 100, 247, 0.6);
        }

        .bank-icon { 
            font-size: 2.2rem; 
            color: #ffffff;
        }

        .brand-title {
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .brand-title span {
            color: #4364f7;
        }

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

        .form-label {
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 8px;
        }

        /* Cleaned styling matching the layout exactly */
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper .input-icon {
            position: absolute;
            left: 16px;
            color: #8da2bb;
            font-size: 1.1rem;
            pointer-events: none;
        }

        .form-control { 
            background-color: rgba(5, 19, 48, 0.6) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 10px !important;
            color: #ffffff !important;
            padding: 14px 16px 14px 45px !important;
            font-size: 1rem;
        }

        .form-control::placeholder {
            color: #4b617c;
        }

        .form-control:focus { 
            border-color: #008cff !important; 
            box-shadow: 0 0 12px rgba(0, 140, 255, 0.5) !important; 
        }

        .btn-toggle-visibility {
            position: absolute;
            right: 16px;
            background: transparent;
            border: none;
            color: #8da2bb;
            padding: 0;
            z-index: 5;
        }
        .btn-toggle-visibility:hover {
            color: #ffffff;
        }

        /* Solid vivid blue accent button */
        .btn-login { 
            background: linear-gradient(90deg, #0072ff, #00c6ff); 
            border: none !important; 
            border-radius: 10px !important;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 14px !important;
            color: white;
            box-shadow: 0 5px 20px rgba(0, 114, 255, 0.4);
            transition: all 0.3s ease;
        }
        .btn-login:hover { 
            opacity: 0.95;
            box-shadow: 0 5px 25px rgba(0, 198, 255, 0.6);
            transform: translateY(-1px);
        }

        /* Custom outline buttons styled precisely like reference link list */
        .btn-portal-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 12px;
            margin-top: 12px;
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 10px;
            color: #ffffff;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn-portal-link i {
            margin-right: 10px;
            color: #8da2bb;
            font-size: 1.05rem;
        }
        .btn-portal-link:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.4) !important;
            color: #ffffff;
        }

        .alert {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.4);
            color: #ff858d;
            border-radius: 10px;
        }
        .alert-warning {
            background-color: rgba(255, 193, 7, 0.15);
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: #ffe082;
            border-radius: 10px;
        }
    </style>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center">
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="bank-icon-wrapper">
                <i class="fas fa-university bank-icon"></i>
            </div>
            <h3 class="brand-title">Secure<span>Bank</span></h3>
            <p class="portal-subtitle">User Login Portal</p>
        </div>

        <?php if ($timeout_msg): ?>
            <div class="alert alert-warning py-2 small mb-3"><i class="fas fa-clock me-2"></i>Session expired. Please login again.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small mb-3"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" class="form-control" placeholder="Enter your username" required autocomplete="off">
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
            <a href="register.php" class="btn-portal-link"><i class="fas fa-user-plus"></i>Create Account</a>
            <a href="forgot_password.php" class="btn-portal-link"><i class="fas fa-key"></i>Forgot Password</a>
        </div>

        <div class="text-center mt-4 pt-2" style="font-size: 12px; color: #64748b;">
            <i class="fas fa-shield-alt me-1"></i> © 2026 SecureBank. All rights reserved.
            <div style="color: #0072ff; margin-top: 3px; font-weight: 500;">Your security is our priority</div>
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