<?php
require 'auth.php';



$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    die("Invalid account ID.");
}

// Fetch Account and User details
$stmt = mysqli_prepare($conn, "SELECT a.*, u.full_name, u.email, u.phone, u.status as user_status FROM accounts a JOIN users u ON a.user_id = u.user_id WHERE a.account_id=?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$account = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$account) {
    die("Account not found.");
}

$error = '';
$success = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } else {
        $action = $_POST['action'] ?? '';
        $new_status = '';
        $audit_action = '';

        if ($action === 'freeze' && $account['status'] === 'active') {
            $new_status = 'frozen';
            $audit_action = 'account_frozen';
        } elseif ($action === 'close' && in_array($account['status'], ['active', 'frozen'])) {
            $new_status = 'closed';
            $audit_action = 'account_closed';
        } elseif ($action === 'activate' && in_array($account['status'], ['frozen', 'closed'])) {
            $new_status = 'active';
            $audit_action = 'account_activated';
        } else {
            $error = "Invalid status transition.";
        }

        if ($new_status && !$error) {
            $upd = mysqli_prepare($conn, "UPDATE accounts SET status=? WHERE account_id=?");
            mysqli_stmt_bind_param($upd, 'si', $new_status, $id);
            if (mysqli_stmt_execute($upd)) {
                // Update local array to reflect changes immediately
                $account['status'] = $new_status;
                $success = "Account status updated successfully to " . ucfirst($new_status) . ".";

                // Log audit
                $details = "Changed account {$account['account_number']} status to {$new_status}";
                logAudit($conn, 'admin', $_SESSION['admin_id'], $audit_action, $details, $account['account_id']);
            } else {
                $error = "Database update failed.";
            }
        }
    }
}

// Fetch recent transactions for this account
$txn_stmt = mysqli_prepare($conn, "SELECT * FROM transactions WHERE account_id=? ORDER BY created_at DESC LIMIT 50");
mysqli_stmt_bind_param($txn_stmt, 'i', $id);
mysqli_stmt_execute($txn_stmt);
$transactions = mysqli_stmt_get_result($txn_stmt);

$active = 'accounts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Details - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/premium-theme.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .details-card { background: var(--surface-light); padding: 25px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); }
        .details-card h3 { margin-top: 0; color: var(--accent-blue); margin-bottom: 20px; font-size: 18px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        .row-item { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .row-item span:first-child { color: var(--text-muted); }
        .row-item span:last-child { font-weight: 600; color: white; }
        
        .action-bar { background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; margin-top: 20px; display: flex; gap: 15px; }
        .btn-freeze { background: #ffc107; color: #000; border: none; }
        .btn-activate { background: #00ffa3; color: #000; border: none; }
        .btn-close { background: #ff3e6c; color: #fff; border: none; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px); z-index: 1000; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: var(--surface); padding: 30px; border-radius: 16px; width: 100%; max-width: 400px; border: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .modal-content h3 { margin-top: 0; margin-bottom: 15px; }
        .modal-content p { color: var(--text-muted); margin-bottom: 25px; }
        .modal-actions { display: flex; gap: 15px; justify-content: center; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><i class="fa-solid fa-file-invoice" style="color: var(--accent-blue);"></i> Account Details</h1>
                <p>Account No: <?= htmlspecialchars($account['account_number']) ?></p>
            </div>
            <a href="accounts.php" class="btn" style="background: rgba(255,255,255,0.1); color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px;"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </header>

        <?php if ($error): ?>
            <div class="alert-flash error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-flash success"><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="details-grid">
            <div class="details-card">
                <h3>Account Information</h3>
                <div class="row-item"><span>Status</span> <span class="badge-status badge-<?= $account['status'] ?>"><?= ucfirst($account['status']) ?></span></div>
                <div class="row-item"><span>Balance</span> <span><?= formatCurrency($account['balance']) ?></span></div>
                <div class="row-item"><span>Type</span> <span><?= ucfirst($account['account_type']) ?></span></div>
                <div class="row-item"><span>Created On</span> <span><?= date('d M Y, h:i A', strtotime($account['created_at'])) ?></span></div>
                
                <div class="action-bar">
                    <?php if ($account['status'] === 'active'): ?>
                        <button class="btn btn-freeze" onclick="confirmAction('freeze', 'Freeze Account', 'Are you sure you want to freeze this account? All transactions will be blocked.')"><i class="fa-solid fa-snowflake"></i> Freeze</button>
                        <button class="btn btn-close" onclick="confirmAction('close', 'Close Account', 'Are you sure you want to permanently close this account?')"><i class="fa-solid fa-ban"></i> Close</button>
                    <?php elseif ($account['status'] === 'frozen'): ?>
                        <button class="btn btn-activate" onclick="confirmAction('activate', 'Activate Account', 'Are you sure you want to reactivate this account?')"><i class="fa-solid fa-bolt"></i> Activate</button>
                        <button class="btn btn-close" onclick="confirmAction('close', 'Close Account', 'Are you sure you want to permanently close this account?')"><i class="fa-solid fa-ban"></i> Close</button>
                    <?php elseif ($account['status'] === 'closed'): ?>
                        <button class="btn btn-activate" onclick="confirmAction('activate', 'Activate Account', 'Are you sure you want to reopen and reactivate this account?')"><i class="fa-solid fa-bolt"></i> Activate</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="details-card">
                <h3>Owner Information</h3>
                <div class="row-item"><span>Full Name</span> <span><?= htmlspecialchars($account['full_name']) ?></span></div>
                <div class="row-item"><span>Email</span> <span><?= htmlspecialchars($account['email']) ?></span></div>
                <div class="row-item"><span>Phone</span> <span><?= htmlspecialchars($account['phone']) ?></span></div>
                <div class="row-item"><span>User Status</span> <span><span class="badge-status badge-<?= $account['user_status'] ?>"><?= ucfirst($account['user_status']) ?></span></span></div>
            </div>
        </div>

        <div class="table-container">
            <h3 style="margin-bottom: 20px;">Recent Transactions</h3>
            <table>
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th style="text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                <?php $count=0; while ($txn = mysqli_fetch_assoc($transactions)): $count++; ?>
                    <tr>
                        <td><?= htmlspecialchars($txn['reference_no']) ?></td>
                        <td><?= ucfirst(str_replace('_', ' ', $txn['type'])) ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($txn['created_at'])) ?></td>
                        <td style="text-align:right; font-weight:600; color: <?= in_array($txn['type'], ['deposit','transfer_in']) ? '#00ffa3' : '#ff3e6c' ?>;">
                            <?= in_array($txn['type'], ['deposit','transfer_in']) ? '+' : '-' ?><?= formatCurrency($txn['amount']) ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($count === 0): ?>
                    <tr><td colspan="4" class="empty-state">No transactions recorded for this account.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Confirmation Modal -->
<div class="modal" id="confirmModal">
    <div class="modal-content">
        <h3 id="modalTitle">Confirm Action</h3>
        <p id="modalDesc">Are you sure?</p>
        <form method="POST" id="actionForm">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="action" id="actionInput" value="">
            <div class="modal-actions">
                <button type="button" class="btn" onclick="closeModal()" style="background: rgba(255,255,255,0.1); color: white; border: none;">Cancel</button>
                <button type="submit" class="btn" id="modalConfirmBtn" style="background: var(--accent-blue); color: white; border: none;">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmAction(action, title, desc) {
    document.getElementById('actionInput').value = action;
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalDesc').innerText = desc;
    
    const btn = document.getElementById('modalConfirmBtn');
    if(action === 'freeze') { btn.style.background = '#ffc107'; btn.style.color = '#000'; }
    else if(action === 'close') { btn.style.background = '#ff3e6c'; btn.style.color = '#fff'; }
    else if(action === 'activate') { btn.style.background = '#00ffa3'; btn.style.color = '#000'; }
    
    document.getElementById('confirmModal').classList.add('show');
}
function closeModal() {
    document.getElementById('confirmModal').classList.remove('show');
}
</script>

</body>
</html>
