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
        $error = "Minimum deposit amount is ₹100.";
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

            $new_balance = $locked_account['balance'] + $amount;
            $ref_no      = generateReferenceNo();

            // Update balance
            $stmt2 = mysqli_prepare($conn, "UPDATE accounts SET balance=? WHERE account_id=?");
            mysqli_stmt_bind_param($stmt2, 'di', $new_balance, $locked_account['account_id']);
            mysqli_stmt_execute($stmt2);

            // Insert transaction
            $type = 'deposit';
            $stmt3 = mysqli_prepare($conn, "INSERT INTO transactions (account_id, type, amount, balance_after, reference_no, note) VALUES (?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt3, 'isddss', $locked_account['account_id'], $type, $amount, $new_balance, $ref_no, $note);
            mysqli_stmt_execute($stmt3);

            $txn_id = mysqli_insert_id($conn);
            mysqli_commit($conn);
            $success = "✅ Deposit of " . formatCurrency($amount) . " successful! Reference: <strong>$ref_no</strong> <br><br> <a href='receipt.php?id=$txn_id' target='_blank' class='btn' style='background:rgba(255,255,255,0.1); color:white; padding:5px 10px; font-size:12px;'><i class='fa-solid fa-file-pdf'></i> Download Receipt</a>";
            logAudit($conn, 'user', $user_id, 'user_deposit', "Deposited " . formatCurrency($amount) . " (Ref: $ref_no)", $locked_account['account_id'], $txn_id);
            sendNotification($conn, $user_id, "Deposit Successful", "Your deposit of " . formatCurrency($amount) . " was successful. Ref: $ref_no");
            // Refresh account data
            $account['balance'] = $new_balance;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Failed to process deposit.";
        }
        }
    }
}

$active = 'deposit';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit - <?= APP_NAME ?></title>
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
                <h1><i class="fa-solid fa-wallet" style="color: var(--accent-blue);"></i> Deposit Money</h1>
                <p>Add funds to your SecureBank account.</p>
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
                    <div class="mb-4 form-group">
                        <label>Account Number</label>
                        <input type="text" class="form-control" value="<?= $account['account_number'] ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Amount (₹)</label>
                        <input type="number" name="amount" class="form-control" placeholder="Enter amount" min="100" required>
                        <span class="hint">Minimum deposit: ₹100</span>
                    </div>
                    <div class="form-group">
                        <label>Note (Optional)</label>
                        <input type="text" name="note" class="form-control" placeholder="e.g. Salary, Savings...">
                    </div>
                    <div class="form-group">
                        <label>Transaction PIN</label>
                        <input type="password" name="pin" class="form-control" placeholder="Enter your 4-digit PIN" maxlength="4" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn-glow btn-block">
                        <i class="fa-solid fa-plus-circle"></i> Deposit Money
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>
