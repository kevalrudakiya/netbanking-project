<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireUserLogin();

$user_id = $_SESSION['user_id'];

// ---- Load user profile ----
$stmt = mysqli_prepare($conn, "SELECT full_name, email, phone, status, created_at FROM users WHERE user_id=?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// ---- Load account ----
$account = validateAccountStatus($conn, $user_id, 'view');

$hasAccount = (bool) $account;

$recent = [];
$income = 0.0;
$expense = 0.0;

if ($hasAccount) {
    // ---- Recent transactions (last 5) ----
    $stmt = mysqli_prepare($conn, "SELECT * FROM transactions WHERE account_id=? ORDER BY created_at DESC LIMIT 5");
    mysqli_stmt_bind_param($stmt, 'i', $account['account_id']);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $recent[] = $row; }

    // ---- This month's income / expense ----
    $stmt = mysqli_prepare($conn, "SELECT
            COALESCE(SUM(CASE WHEN type IN ('deposit','transfer_in') THEN amount ELSE 0 END),0) AS income,
            COALESCE(SUM(CASE WHEN type IN ('withdraw','transfer_out') THEN amount ELSE 0 END),0) AS expense
        FROM transactions
        WHERE account_id=? AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())");
    mysqli_stmt_bind_param($stmt, 'i', $account['account_id']);
    mysqli_stmt_execute($stmt);
    $agg = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $income  = (float) $agg['income'];
    $expense = (float) $agg['expense'];
}

// ---- Small helpers ----
function maskAccountNumber($num) {
    $num = (string) $num;
    $len = strlen($num);
    if ($len <= 4) return $num;
    return str_repeat('*', $len - 4) . substr($num, -4);
}
function timeGreeting() {
    $h = (int) date('G');
    if ($h < 12) return 'Good morning';
    if ($h < 17) return 'Good afternoon';
    return 'Good evening';
}
function txnMeta($type) {
    switch ($type) {
        case 'deposit':      return ['label' => 'Deposit',        'sign' => '+', 'class' => 'status-deposit',  'icon' => 'fa-arrow-down'];
        case 'withdraw':     return ['label' => 'Withdrawal',     'sign' => '-', 'class' => 'status-withdraw', 'icon' => 'fa-arrow-up'];
        case 'transfer_in':  return ['label' => 'Transfer In',    'sign' => '+', 'class' => 'status-deposit',  'icon' => 'fa-arrow-down'];
        case 'transfer_out': return ['label' => 'Transfer Out',   'sign' => '-', 'class' => 'status-withdraw', 'icon' => 'fa-arrow-up'];
        default:             return ['label' => ucfirst($type),   'sign' => '',  'class' => '',                'icon' => 'fa-circle'];
    }
}

$maxFlow = max($income, $expense, 1);
$incomePct  = round(($income  / $maxFlow) * 100);
$expensePct = round(($expense / $maxFlow) * 100);

$active = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/premium-theme.css?v=2">
    <link rel="stylesheet" href="../assets/css/app-theme.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="dashboard-container">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <header class="page-header">
            <div>
                <h1><?= timeGreeting() ?>, <?= htmlspecialchars(explode(' ', $user['full_name'])[0] ?? 'there') ?>!</h1>
                <p>Here's what's happening with your account today.</p>
            </div>
            <div style="display: flex; gap: 20px; align-items: center;">
                <button type="button" id="antiGravityToggle" style="background: rgba(0,255,163,0.1); border: 1px solid rgba(0,255,163,0.3); color: #00ffa3; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-rocket"></i> Anti-Gravity Mode
                </button>
                <div class="profile-pill">
                    <div style="text-align: right;">
                        <div style="font-weight: bold;"><?= htmlspecialchars($user['full_name']) ?></div>
                        <div style="font-size: 12px; color: var(--text-muted);">
                            <?= $hasAccount ? 'Acc No: ' . maskAccountNumber($account['account_number']) : 'No account linked' ?>
                        </div>
                    </div>
                    <div class="avatar-circle">
                        <i class="fa-solid fa-user" style="color: var(--accent-blue);"></i>
                    </div>
                </div>
            </div>
        </header>

        <?php showFlash(); ?>

        <?php if (!$hasAccount): ?>
            <div class="alert-flash info">
                <i class="fa-solid fa-circle-info"></i>
                No account is linked to your profile yet. Please contact support to have one set up.
            </div>
        <?php else: ?>

        <!-- Balance & monthly metrics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="balance-row">
                    <div style="color: var(--text-muted); font-size: 14px;">Available Balance</div>
                    <button type="button" class="icon-btn" id="toggleBalanceBtn" title="Show/hide balance">
                        <i class="fa-solid fa-eye" id="toggleBalanceIcon"></i>
                    </button>
                </div>
                <div class="balance-amount" id="balanceAmount"><?= formatCurrency($account['balance']) ?></div>
                <div class="acc-number">
                    <code id="accNumber"><?= maskAccountNumber($account['account_number']) ?></code>
                    <button type="button" class="icon-btn" id="copyAccBtn" title="Copy account number" data-full="<?= htmlspecialchars($account['account_number']) ?>">
                        <i class="fa-regular fa-copy"></i>
                    </button>
                    <span class="badge-status badge-<?= $account['status'] ?>"><?= ucfirst($account['status']) ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div style="color: var(--text-muted); font-size: 14px;">Total Income (This Month)</div>
                <div class="balance-amount" style="color: #00ffa3;">+<?= formatCurrency($income) ?></div>
            </div>
            <div class="stat-card">
                <div style="color: var(--text-muted); font-size: 14px;">Total Expenses (This Month)</div>
                <div class="balance-amount" style="color: #ff3e6c;">-<?= formatCurrency($expense) ?></div>
            </div>
        </div>

        <div class="two-col">
            <!-- Recent Transactions -->
            <div class="table-container">
                <div class="head-row">
                    <h3 style="margin: 0;">Recent Transactions</h3>
                    <a href="statement.php">View all <i class="fa-solid fa-arrow-right"></i></a>
                </div>
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
                        <?php if (empty($recent)): ?>
                            <tr><td colspan="4" class="empty-state">
                                <i class="fa-regular fa-folder-open" style="font-size: 22px; display:block; margin-bottom: 8px;"></i>
                                No transactions yet
                            </td></tr>
                        <?php else: foreach ($recent as $txn): $meta = txnMeta($txn['type']); ?>
                            <tr>
                                <td><?= htmlspecialchars($txn['reference_no']) ?></td>
                                <td>
                                    <span class="txn-type <?= $meta['class'] ?>">
                                        <i class="fa-solid <?= $meta['icon'] ?>"></i> <?= $meta['label'] ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y', strtotime($txn['created_at'])) ?></td>
                                <td class="<?= $meta['class'] ?>" style="text-align:right; font-weight:600;">
                                    <?= $meta['sign'] ?><?= formatCurrency($txn['amount']) ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Side column: cash flow + profile -->
            <div style="display:flex; flex-direction:column; gap:25px;">
                <div class="table-container">
                    <h3 style="margin: 0 0 15px 0;">Cash Flow (This Month)</h3>
                    <div class="flow-card">
                        <div class="flow-row">
                            <div class="flow-label">Income</div>
                            <div class="flow-track"><div class="flow-fill income" style="width: <?= $incomePct ?>%;"></div></div>
                            <div class="flow-value" style="color:#00ffa3;">+<?= formatCurrency($income) ?></div>
                        </div>
                        <div class="flow-row">
                            <div class="flow-label">Expense</div>
                            <div class="flow-track"><div class="flow-fill expense" style="width: <?= $expensePct ?>%;"></div></div>
                            <div class="flow-value" style="color:#ff3e6c;">-<?= formatCurrency($expense) ?></div>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <h3 style="margin: 0 0 15px 0;">Profile Summary</h3>
                    <div class="profile-card">
                        <div class="row"><span>Full Name</span><span><?= htmlspecialchars($user['full_name']) ?></span></div>
                        <div class="row"><span>Email</span><span><?= htmlspecialchars($user['email']) ?></span></div>
                        <div class="row"><span>Phone</span><span><?= htmlspecialchars($user['phone']) ?></span></div>
                        <div class="row"><span>Member Since</span><span><?= date('d M Y', strtotime($user['created_at'])) ?></span></div>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </main>
</div>

<script>
    // Show / hide balance
    const balanceEl = document.getElementById('balanceAmount');
    const toggleBtn = document.getElementById('toggleBalanceBtn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            const icon = document.getElementById('toggleBalanceIcon');
            balanceEl.classList.toggle('blurred');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }

    // Copy account number
    const copyBtn = document.getElementById('copyAccBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            const full = copyBtn.getAttribute('data-full');
            navigator.clipboard.writeText(full).then(function () {
                const icon = copyBtn.querySelector('i');
                icon.classList.remove('fa-regular', 'fa-copy');
                icon.classList.add('fa-solid', 'fa-check');
                setTimeout(function () {
                    icon.classList.remove('fa-solid', 'fa-check');
                    icon.classList.add('fa-regular', 'fa-copy');
                }, 1500);
            });
        });
    }
    // Auto-scale huge numbers so they fit on one line
    document.querySelectorAll('.balance-amount').forEach(el => {
        let fontSize = 32;
        // Keep reducing font size until it fits or reaches a minimum of 14px
        while (el.scrollWidth > el.clientWidth && fontSize > 14) {
            fontSize--;
            el.style.fontSize = fontSize + 'px';
        }
    });
    // Anti-Gravity logic exclusively for Dashboard
    const antiGravityBtn = document.getElementById('antiGravityToggle');
    
    function setAntiGravityState(isActive) {
        if (isActive) {
            document.body.classList.add('anti-gravity-mode');
            if (antiGravityBtn) {
                antiGravityBtn.style.color = '#ff3e6c';
                antiGravityBtn.style.background = 'rgba(255,62,108,0.1)';
                antiGravityBtn.style.borderColor = 'rgba(255,62,108,0.3)';
                antiGravityBtn.innerHTML = '<i class="fa-solid fa-meteor"></i> Disable Gravity';
            }
            localStorage.setItem('antiGravityMode', 'true');
        } else {
            document.body.classList.remove('anti-gravity-mode');
            if (antiGravityBtn) {
                antiGravityBtn.style.color = '#00ffa3';
                antiGravityBtn.style.background = 'rgba(0,255,163,0.1)';
                antiGravityBtn.style.borderColor = 'rgba(0,255,163,0.3)';
                antiGravityBtn.innerHTML = '<i class="fa-solid fa-rocket"></i> Anti-Gravity Mode';
            }
            localStorage.setItem('antiGravityMode', 'false');
        }
    }

    // Initialize state on load
    if (localStorage.getItem('antiGravityMode') === 'true') {
        setAntiGravityState(true);
    }

    if (antiGravityBtn) {
        antiGravityBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const isActive = document.body.classList.contains('anti-gravity-mode');
            setAntiGravityState(!isActive);
        });
    }
</script>

</body>
</html>
