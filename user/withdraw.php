<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireUserLogin();

$user_id = $_SESSION['user_id'];
$account = validateAccountStatus($conn, $user_id, 'financial');

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        $amount = floatval($_POST['amount']);
    $pin    = $_POST['pin'];
    $note   = sanitize($_POST['note']);

    if ($amount <= 0) {
        $error = "Please enter a valid amount.";
    } elseif ($amount < 100) {
        $error = "Minimum withdrawal amount is ₹100.";
    } elseif ($amount > $account['balance']) {
        $error = "Insufficient balance. Available: " . formatCurrency($account['balance']);
    } elseif (($account['balance'] - $amount) < 500) {
        $error = "You must maintain a minimum balance of ₹500.";
    } elseif (!verifyPassword($pin, $account['pin'])) {
        $error = "Incorrect PIN. Please try again.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            $stmt_lock = mysqli_prepare($conn, "SELECT * FROM accounts WHERE account_id=? FOR UPDATE");
            mysqli_stmt_bind_param($stmt_lock, 'i', $account['account_id']);
            mysqli_stmt_execute($stmt_lock);
            $locked_account = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_lock));

            if (!$locked_account || $locked_account['status'] !== 'active') {
                throw new Exception("Account status changed. Transaction blocked.");
            }

            if ($amount > $locked_account['balance']) {
                throw new Exception("Insufficient balance.");
            }
            if (($locked_account['balance'] - $amount) < 500) {
                throw new Exception("You must maintain a minimum balance of ₹500.");
            }

            $new_balance = $locked_account['balance'] - $amount;
            $ref_no      = generateReferenceNo();

            $stmt2 = mysqli_prepare($conn, "UPDATE accounts SET balance=? WHERE account_id=?");
            mysqli_stmt_bind_param($stmt2, 'di', $new_balance, $locked_account['account_id']);
            mysqli_stmt_execute($stmt2);

            $type = 'withdraw';
            $stmt3 = mysqli_prepare($conn, "INSERT INTO transactions (account_id, type, amount, balance_after, reference_no, note) VALUES (?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt3, 'isddss', $locked_account['account_id'], $type, $amount, $new_balance, $ref_no, $note);
            mysqli_stmt_execute($stmt3);

            $txn_id = mysqli_insert_id($conn);
            mysqli_commit($conn);
            $success = "✅ Withdrawal of " . formatCurrency($amount) . " successful! Reference: <strong>$ref_no</strong> <br><br> <a href='receipt.php?id=$txn_id' target='_blank' class='btn' style='background:rgba(255,255,255,0.1); color:white; padding:5px 10px; font-size:12px;'><i class='fa-solid fa-file-pdf'></i> Download Receipt</a>";
            logAudit($conn, 'user', $user_id, 'user_withdrawal', "Withdrew " . formatCurrency($amount) . " (Ref: $ref_no)", $locked_account['account_id'], $txn_id);
            sendNotification($conn, $user_id, "Withdrawal Successful", "Your withdrawal of " . formatCurrency($amount) . " was successful. Ref: $ref_no");
            $account['balance'] = $new_balance;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
        }
    }
}

$active = 'withdraw';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdraw - <?= APP_NAME ?></title>
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
                <h1><i class="fa-solid fa-money-bill-transfer" style="color: var(--accent-blue);"></i> Withdraw Money</h1>
                <p>Withdraw funds from your SecureBank account.</p>
            </div>
        </header>

        <div class="balance-pill">
            <small>Current Balance</small>
            <strong><?= formatCurrency($account['balance']) ?></strong>
        </div>

        <div class="form-shell">
            <div class="form-card">
                <?php if ($error): ?>
                    <div class="alert-flash danger"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert-flash success"><?= $success ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <div class="form-group">
                        <label>Account Number</label>
                        <input type="text" class="form-control" value="<?= $account['account_number'] ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Amount (₹)</label>
                        <input type="number" name="amount" class="form-control" placeholder="Enter amount" min="100" required>
                        <span class="hint">Min: ₹100 | Min balance must remain: ₹500</span>
                    </div>
                    <div class="form-group">
                        <label>Note (Optional)</label>
                        <input type="text" name="note" class="form-control" placeholder="e.g. Rent, Shopping...">
                    </div>
                    <div class="form-group">
                        <label>Transaction PIN</label>
                        <input type="password" name="pin" class="form-control" placeholder="Enter your 4-digit PIN" maxlength="4" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn-glow btn-danger btn-block">
                        <i class="fa-solid fa-minus-circle"></i> Withdraw Money
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>
