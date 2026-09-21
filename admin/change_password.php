<?php
require 'auth.php';

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid security token.";
    } else {
        $newPassword = $_POST['password'];
    if (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $hash = hashPassword($newPassword);
        $stmt = mysqli_prepare($conn, "UPDATE admins SET password=? WHERE admin_id=?");
        mysqli_stmt_bind_param($stmt, 'si', $hash, $_SESSION['admin_id']);
        mysqli_stmt_execute($stmt);
        $success = "Password updated successfully.";
    }
    }
}

$active = 'password';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - <?= APP_NAME ?></title>
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
                <h1><i class="fa-solid fa-key" style="color: var(--accent-blue);"></i> Change Admin Password</h1>
                <p>Update the password used to sign in to this admin account.</p>
            </div>
        </header>

        <div class="form-shell">
            <div class="form-card">
                <?php if (!empty($error)): ?>
                    <div class="alert-flash danger"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert-flash success"><?= $success ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter new password" minlength="6" required autocomplete="new-password">
                        <span class="hint">Minimum 6 characters</span>
                    </div>
                    <button type="submit" class="btn-glow btn-block">
                        <i class="fa-solid fa-floppy-disk"></i> Save Password
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>
