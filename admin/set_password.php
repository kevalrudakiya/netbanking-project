<?php
require 'auth.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT * FROM password_reset_requests WHERE request_id=?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$request = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$request) {
    header("Location: password_requests.php");
    exit();
}

$success = '';
if (isset($_POST['save'])) {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid security token.";
    } else {
        $newpass = $_POST['password'];

    if (strlen($newpass) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $hash = hashPassword($newpass);

        $s1 = mysqli_prepare($conn, "UPDATE users SET password=? WHERE user_id=?");
        mysqli_stmt_bind_param($s1, 'si', $hash, $request['user_id']);
        mysqli_stmt_execute($s1);

        $s2 = mysqli_prepare($conn, "UPDATE password_reset_requests SET status='approved' WHERE request_id=?");
        mysqli_stmt_bind_param($s2, 'i', $id);
        mysqli_stmt_execute($s2);

        logAudit($conn, 'admin', $_SESSION['admin_id'], 'admin_password_reset_approve', "Approved password reset for user ID: {$request['user_id']}");

        header("Location: password_requests.php");
        exit();
    }
    }
}

$active = 'requests';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Password - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/premium-theme.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="dashboard-container">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <h1><i class="fa-solid fa-key" style="color: var(--accent-blue);"></i> Set New Password</h1>
                <p>Request #<?= $request['request_id'] ?> — Account <?= htmlspecialchars($request['account_number']) ?></p>
            </div>
        </header>

        <div class="form-shell">
            <div class="form-card">
                <?php if (!empty($error)): ?>
                    <div class="alert-flash danger"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
                <?php endif; ?>

                <div class="table-container" style="margin-bottom: 20px; padding: 18px;">
                    <div class="profile-card">
                        <div class="row"><span>User ID</span><span><?= $request['user_id'] ?></span></div>
                        <div class="row"><span>Account Number</span><span><?= htmlspecialchars($request['account_number']) ?></span></div>
                        <div class="row"><span>Phone</span><span><?= htmlspecialchars($request['phone']) ?></span></div>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter new password" minlength="6" required autocomplete="new-password">
                    </div>
                    <button type="submit" name="save" class="btn-glow btn-block">
                        <i class="fa-solid fa-floppy-disk"></i> Save Password
                    </button>
                </form>

                <div style="margin-top: 15px; text-align: center;">
                    <a href="password_requests.php" style="color: var(--text-muted); font-size: 13px; text-decoration: none;">
                        <i class="fa-solid fa-arrow-left"></i> Back to requests
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>
