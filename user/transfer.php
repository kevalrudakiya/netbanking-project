<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireUserLogin();

$user_id = $_SESSION['user_id'];
$account = validateAccountStatus($conn, $user_id, 'financial');

// Fetch beneficiaries for dropdown
$b_stmt = mysqli_prepare($conn, "SELECT account_number, beneficiary_name, nickname FROM beneficiaries WHERE user_id=? ORDER BY is_favorite DESC, created_at DESC");
mysqli_stmt_bind_param($b_stmt, 'i', $user_id);
mysqli_stmt_execute($b_stmt);
$beneficiaries = mysqli_stmt_get_result($b_stmt);

$pre_to = sanitize($_GET['to'] ?? '');

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        $to_account = sanitize($_POST['to_account']);
    $amount     = floatval($_POST['amount']);
    $pin        = $_POST['pin'];
    $note       = sanitize($_POST['note']);

    if ($to_account === $account['account_number']) {
        $error = "You cannot transfer to your own account.";
    } elseif ($amount <= 0) {
        $error = "Please enter a valid amount greater than 0.";
    } elseif ($amount < 100) {
        $error = "Minimum transfer amount is ₹100.";
    } elseif (!verifyPassword($pin, $account['pin'])) {
        $error = "Incorrect PIN.";
    } else {
        // Receiver lookup and validation before transaction
        $rx_result = validateReceiverForTransfer($conn, $to_account);
        
        if (isset($rx_result['error'])) {
            $error = $rx_result['error'];
        } else {
            $sender_id = $account['account_id'];
            $receiver_id = $rx_result['account_id'];
            
            mysqli_begin_transaction($conn);
            try {
                // Lock rows in deterministic order to prevent MySQL deadlocks
                $lock_ids = [$sender_id, $receiver_id];
                sort($lock_ids);
                
                $stmt_lock = mysqli_prepare($conn, "SELECT * FROM accounts WHERE account_id IN (?, ?) ORDER BY account_id ASC FOR UPDATE");
                mysqli_stmt_bind_param($stmt_lock, 'ii', $lock_ids[0], $lock_ids[1]);
                mysqli_stmt_execute($stmt_lock);
                $lock_res = mysqli_stmt_get_result($stmt_lock);
                
                $locked_sender = null;
                $locked_receiver = null;
                
                while ($row = mysqli_fetch_assoc($lock_res)) {
                    if ($row['account_id'] == $sender_id) {
                        $locked_sender = $row;
                    } elseif ($row['account_id'] == $receiver_id) {
                        $locked_receiver = $row;
                    }
                }

                // Post-lock validations
                if (!$locked_sender || $locked_sender['status'] !== 'active') {
                    throw new Exception("Your account is no longer active or cannot be locked.");
                }
                if (!$locked_receiver || $locked_receiver['status'] !== 'active') {
                    throw new Exception("Receiver account is not active or closed.");
                }
                if ($amount > $locked_sender['balance']) {
                    throw new Exception("Insufficient balance.");
                }
                if (($locked_sender['balance'] - $amount) < 500) {
                    throw new Exception("You must maintain a minimum balance of ₹500.");
                }

                $ref_out = generateReferenceNo();
                $ref_in  = generateReferenceNo();
                
                // Safe relative updates to avoid race conditions
                $s1 = mysqli_prepare($conn, "UPDATE accounts SET balance = balance - ? WHERE account_id = ?");
                mysqli_stmt_bind_param($s1, 'di', $amount, $sender_id);
                if (!mysqli_stmt_execute($s1)) throw new Exception("Failed to deduct amount.");
                
                $s2 = mysqli_prepare($conn, "UPDATE accounts SET balance = balance + ? WHERE account_id = ?");
                mysqli_stmt_bind_param($s2, 'di', $amount, $receiver_id);
                if (!mysqli_stmt_execute($s2)) throw new Exception("Failed to credit amount.");
                
                $sender_balance_after = $locked_sender['balance'] - $amount;
                $receiver_balance_after = $locked_receiver['balance'] + $amount;

                // Sender transaction with related_account_id
                $type_out = 'transfer_out';
                $s3 = mysqli_prepare($conn, "INSERT INTO transactions (account_id, type, amount, balance_after, reference_no, note, related_account_id) VALUES (?,?,?,?,?,?,?)");
                mysqli_stmt_bind_param($s3, 'isddssi', $sender_id, $type_out, $amount, $sender_balance_after, $ref_out, $note, $receiver_id);
                if (!mysqli_stmt_execute($s3)) throw new Exception("Failed to record sender transaction.");

                // Receiver transaction with related_account_id
                $type_in = 'transfer_in';
                $s4 = mysqli_prepare($conn, "INSERT INTO transactions (account_id, type, amount, balance_after, reference_no, note, related_account_id) VALUES (?,?,?,?,?,?,?)");
                mysqli_stmt_bind_param($s4, 'isddssi', $receiver_id, $type_in, $amount, $receiver_balance_after, $ref_in, $note, $sender_id);
                if (!mysqli_stmt_execute($s4)) throw new Exception("Failed to record receiver transaction.");

                $txn_id = mysqli_insert_id($conn);
                mysqli_commit($conn);
                logAudit($conn, 'user', $user_id, 'user_transfer', "Transferred " . formatCurrency($amount) . " to Account: $to_account (Ref: $ref_out)", $sender_id, $txn_id);
                
                sendNotification($conn, $user_id, "Transfer Sent", "You successfully transferred " . formatCurrency($amount) . " to account $to_account. Ref: $ref_out");
                sendNotification($conn, $rx_result['user_id'], "Transfer Received", "You received " . formatCurrency($amount) . " from account {$account['account_number']}. Ref: $ref_in");
                
                $success = "Transfer successful.<br>
                            <strong>Transaction ID:</strong> $ref_out<br>
                            <strong>Amount:</strong> " . formatCurrency($amount) . "<br>
                            <strong>From Account:</strong> {$account['account_number']}<br>
                            <strong>To Account:</strong> $to_account<br>
                            <strong>Date/Time:</strong> " . date('Y-m-d H:i:s') . "<br>
                            <strong>Status:</strong> Completed<br><br>
                            <a href='receipt.php?id=$txn_id' target='_blank' class='btn' style='background:rgba(255,255,255,0.1); color:white; padding:5px 10px; font-size:12px; text-decoration:none;'><i class='fa-solid fa-file-pdf'></i> Download Receipt</a>";
                $account['balance'] = $sender_balance_after;
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Transfer failed. No money was deducted. (" . $e->getMessage() . ")";
            }
        }
        }
    }
}

$active = 'transfer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer - <?= APP_NAME ?></title>
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
                <h1><i class="fa-solid fa-paper-plane" style="color: var(--accent-blue);"></i> Transfer Money</h1>
                <p>Send money instantly to another SecureBank account.</p>
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
                        <label>From Account</label>
                        <input type="text" class="form-control" value="<?= $account['account_number'] ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Select Beneficiary (Optional)</label>
                        <select class="form-control" onchange="document.getElementById('to_account').value = this.value;">
                            <option value="">-- Choose a saved beneficiary --</option>
                            <?php while($b = mysqli_fetch_assoc($beneficiaries)): 
                                $b_name = $b['nickname'] ? $b['nickname'] : $b['beneficiary_name'];
                            ?>
                                <option value="<?= htmlspecialchars($b['account_number']) ?>">
                                    <?= htmlspecialchars($b_name) ?> (<?= htmlspecialchars($b['account_number']) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>To Account Number</label>
                        <input type="text" name="to_account" id="to_account" class="form-control" placeholder="Enter receiver's account number" value="<?= htmlspecialchars($pre_to) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Amount (₹)</label>
                        <input type="number" name="amount" class="form-control" placeholder="Enter amount" min="100" required>
                        <span class="hint">Min: ₹100 | Min balance must remain: ₹500</span>
                    </div>
                    <div class="form-group">
                        <label>Note (Optional)</label>
                        <input type="text" name="note" class="form-control" placeholder="e.g. Paying rent...">
                    </div>
                    <div class="form-group">
                        <label>Transaction PIN</label>
                        <input type="password" name="pin" class="form-control" placeholder="Enter your 4-digit PIN" maxlength="4" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn-glow btn-block">
                        <i class="fa-solid fa-paper-plane"></i> Transfer Money
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>
