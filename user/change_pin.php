<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireUserLogin();

$user_id = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM accounts WHERE user_id=?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$account = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_pin  = $_POST['old_pin'];
    $new_pin  = $_POST['new_pin'];
    $conf_pin = $_POST['confirm_pin'];

    if (!preg_match('/^[0-9]{4}$/', $old_pin) || !preg_match('/^[0-9]{4}$/', $new_pin)) {
        $error = "PIN must be exactly 4 digits.";
    } elseif ($new_pin !== $conf_pin) {
        $error = "New PIN and Confirm PIN do not match.";
    } elseif (!verifyPassword($old_pin, $account['pin'])) {
        $error = "Current PIN is incorrect.";
    } elseif ($old_pin === $new_pin) {
        $error = "New PIN cannot be the same as old PIN.";
    } else {
        $hashed = hashPassword($new_pin);
        $stmt2 = mysqli_prepare($conn, "UPDATE accounts SET pin=? WHERE account_id=?");
        mysqli_stmt_bind_param($stmt2, 'si', $hashed, $account['account_id']);
        mysqli_stmt_execute($stmt2);
        $success = "✅ PIN changed successfully!";
    }
}

$active = 'change_pin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change PIN - <?= APP_NAME ?></title>
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
                <h1><i class="fa-solid fa-key" style="color: var(--accent-blue);"></i> Change Transaction PIN</h1>
                <p>Update the 4-digit PIN used to authorize your transactions.</p>
            </div>
        </header>

        <div class="form-shell">
            <div class="form-card">
                <?php if ($error): ?>
                    <div class="alert-flash danger"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert-flash success"><?= $success ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Current PIN</label>
                        <input type="password" name="old_pin" class="form-control" placeholder="Enter current 4-digit PIN" maxlength="4" required autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label>New PIN</label>
                        <input type="password" name="new_pin" class="form-control" placeholder="Enter new 4-digit PIN" maxlength="4" required autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label>Confirm New PIN</label>
                        <input type="password" name="confirm_pin" class="form-control" placeholder="Repeat new 4-digit PIN" maxlength="4" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn-glow btn-warning btn-block">
                        <i class="fa-solid fa-floppy-disk"></i> Change PIN
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>
