<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

$msg = '';
$msgClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $msg = "Invalid security token. Please try again.";
        $msgClass = "danger";
    } else {
        $phone          = sanitize($_POST['phone']);
        $account_number = sanitize($_POST['account_number']);

    if (empty($phone) || empty($account_number)) {
        $msg = "Please enter both your phone number and account number.";
        $msgClass = "danger";
    } else {
        $stmt = mysqli_prepare($conn, "
            SELECT u.user_id
            FROM users u
            INNER JOIN accounts a ON u.user_id = a.user_id
            WHERE TRIM(u.phone) = ? AND TRIM(a.account_number) = ?
            LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt, 'ss', $phone, $account_number);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $row     = mysqli_fetch_assoc($result);
            $user_id = $row['user_id'];

            // Prevent duplicate pending requests for the same account
            $check = mysqli_prepare($conn, "SELECT request_id FROM password_reset_requests WHERE user_id=? AND status='pending'");
            mysqli_stmt_bind_param($check, 'i', $user_id);
            mysqli_stmt_execute($check);
            $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($check));

            if ($existing) {
                $msg = "You already have a pending reset request. Please wait for an administrator to process it.";
                $msgClass = "info";
            } else {
                $insert = mysqli_prepare($conn, "
                    INSERT INTO password_reset_requests (user_id, account_number, phone, status)
                    VALUES (?, ?, ?, 'pending')
                ");
                mysqli_stmt_bind_param($insert, 'iss', $user_id, $account_number, $phone);

                if (mysqli_stmt_execute($insert)) {
                    $msg = "Your reset request has been submitted. An administrator will contact you once it's processed.";
                    $msgClass = "success";
                    logAudit($conn, 'user', $user_id, 'user_password_reset_request', "Requested password reset for account $account_number");
                    sendNotification($conn, 0, "Password Reset Request", "User ID $user_id (Account: $account_number) requested a password reset.", 'admin');
                } else {
                    $msg = "Something went wrong. Please try again later.";
                    $msgClass = "danger";
                }
            }
        } else {
            $msg = "We couldn't find an account matching that phone number and account number.";
            $msgClass = "danger";
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
    <title>Forgot Password - <?= APP_NAME ?></title>
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
        .form-control:focus { border-color: #4364f7 !important; box-shadow: 0 0 12px rgba(67, 100, 247, 0.5) !important; }
        .btn-primary {
            background: linear-gradient(90deg, #0052d4, #4364f7);
            border: none !important;
            border-radius: 10px !important;
            font-weight: 600;
            font-size: 1.05rem;
            padding: 14px !important;
            color: white;
            box-shadow: 0 5px 20px rgba(67, 100, 247, 0.4);
            transition: all 0.3s ease;
        }
        .btn-primary:hover { opacity: 0.95; box-shadow: 0 5px 25px rgba(67, 100, 247, 0.6); transform: translateY(-1px); }
        .btn-portal-link {
            display: flex; align-items: center; justify-content: center; width: 100%;
            padding: 12px; margin-top: 12px; background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.15) !important; border-radius: 10px;
            color: #ffffff; text-decoration: none; font-size: 0.95rem; font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn-portal-link i { margin-right: 10px; color: #8da2bb; font-size: 1.05rem; }
        .btn-portal-link:hover { background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 255, 255, 0.4) !important; color: #ffffff; }
        .alert-success { background-color: rgba(0, 255, 163, 0.12); border: 1px solid rgba(0, 255, 163, 0.35); color: #4ade9c; border-radius: 10px; }
        .alert-danger  { background-color: rgba(220, 53, 69, 0.2); border: 1px solid rgba(220, 53, 69, 0.4); color: #ff858d; border-radius: 10px; }
        .alert-info    { background-color: rgba(67, 100, 247, 0.15); border: 1px solid rgba(67, 100, 247, 0.4); color: #8db4ff; border-radius: 10px; }
    </style>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center">
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="bank-icon-wrapper">
                <i class="fas fa-unlock-keyhole bank-icon"></i>
            </div>
            <h3 class="brand-title">Secure<span>Bank</span></h3>
            <p class="portal-subtitle">Forgot Password</p>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgClass ?> py-2 small mb-3">
                <i class="fas fa-<?= $msgClass === 'success' ? 'circle-check' : ($msgClass === 'info' ? 'circle-info' : 'exclamation-circle') ?> me-2"></i>
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($msgClass !== 'success'): ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <div class="input-wrapper">
                    <i class="fas fa-phone input-icon"></i>
                    <input type="text" name="phone" class="form-control" placeholder="Enter your registered phone number" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Account Number</label>
                <div class="input-wrapper">
                    <i class="fas fa-hashtag input-icon"></i>
                    <input type="text" name="account_number" class="form-control" placeholder="Enter your account number" required>
                </div>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Send Reset Request
                </button>
            </div>
        </form>
        <?php endif; ?>

        <div class="mt-3">
            <a href="login.php" class="btn-portal-link"><i class="fas fa-arrow-left"></i>Back to Login</a>
        </div>

        <div class="text-center mt-4 pt-2" style="font-size: 12px; color: #64748b;">
            <i class="fas fa-shield-alt me-1"></i> Your request will be reviewed by a bank administrator.
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
