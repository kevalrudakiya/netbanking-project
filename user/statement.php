<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireUserLogin();

$user_id = $_SESSION['user_id'];
$account = validateAccountStatus($conn, $user_id, 'view');

if (!$account) {
    die("No active account found.");
}

// Pagination
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Filters
$f_txn_id = trim($_GET['txn_id'] ?? '');
$f_type = trim($_GET['type'] ?? 'all');
$f_flow = trim($_GET['flow'] ?? ''); // credit or debit
$f_min = trim($_GET['min_amount'] ?? '');
$f_max = trim($_GET['max_amount'] ?? '');
$f_start = trim($_GET['start_date'] ?? '');
$f_end = trim($_GET['end_date'] ?? '');
$f_sort = trim($_GET['sort'] ?? 'newest');

$where = ["account_id = ?"];
$params = [$account['account_id']];
$types = "i";

if ($f_txn_id !== '') {
    $where[] = "reference_no LIKE ?";
    $params[] = "%$f_txn_id%"; $types .= "s";
}
if ($f_type !== 'all' && $f_type !== '') {
    $where[] = "type = ?";
    $params[] = $f_type; $types .= "s";
}
if ($f_flow === 'credit') {
    $where[] = "type IN ('deposit', 'transfer_in')";
} elseif ($f_flow === 'debit') {
    $where[] = "type IN ('withdraw', 'transfer_out')";
}
if (is_numeric($f_min)) {
    $where[] = "amount >= ?";
    $params[] = $f_min; $types .= "d";
}
if (is_numeric($f_max)) {
    $where[] = "amount <= ?";
    $params[] = $f_max; $types .= "d";
}
if ($f_start !== '') {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $f_start; $types .= "s";
}
if ($f_end !== '') {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $f_end; $types .= "s";
}

$order_by = "transaction_id DESC";
if ($f_sort === 'oldest') $order_by = "transaction_id ASC";
if ($f_sort === 'amt_high') $order_by = "amount DESC";
if ($f_sort === 'amt_low') $order_by = "amount ASC";

$where_clause = implode(" AND ", $where);
$base_sql = "FROM transactions WHERE $where_clause";

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $sql = "SELECT transaction_id, reference_no, type, amount, balance_after, note, created_at " . $base_sql . " ORDER BY " . $order_by;
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    exportToCsv('Statement_' . $account['account_number'] . '_' . date('Ymd_His') . '.csv', mysqli_stmt_get_result($stmt));
}

// Count total
$count_sql = "SELECT COUNT(*) as c " . $base_sql;
$stmt_c = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($stmt_c, $types, ...$params);
mysqli_stmt_execute($stmt_c);
$total_rows = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_c))['c'];
$total_pages = ceil($total_rows / $limit);

// Fetch
$sql = "SELECT * " . $base_sql . " ORDER BY " . $order_by . " LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
$types_fetch = $types . "ii";
$params_fetch = $params;
$params_fetch[] = $limit;
$params_fetch[] = $offset;
mysqli_stmt_bind_param($stmt, $types_fetch, ...$params_fetch);
mysqli_stmt_execute($stmt);
$transactions = mysqli_stmt_get_result($stmt);

function txnMeta($type) {
    switch ($type) {
        case 'deposit':      return ['label' => 'Deposit',        'sign' => '+', 'class' => 'status-deposit',  'icon' => 'fa-arrow-down'];
        case 'withdraw':     return ['label' => 'Withdrawal',     'sign' => '-', 'class' => 'status-withdraw', 'icon' => 'fa-arrow-up'];
        case 'transfer_in':  return ['label' => 'Transfer In',    'sign' => '+', 'class' => 'status-deposit',  'icon' => 'fa-arrow-down'];
        case 'transfer_out': return ['label' => 'Transfer Out',   'sign' => '-', 'class' => 'status-withdraw', 'icon' => 'fa-arrow-up'];
        default:             return ['label' => ucfirst($type),   'sign' => '',  'class' => '',                'icon' => 'fa-circle'];
    }
}

$active = 'statement';
$exportPdfUrl = "statement_pdf.php?" . http_build_query($_GET);
$exportCsvUrl = "?" . http_build_query(array_merge($_GET, ['export' => 'csv']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statement - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/premium-theme.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-card { background: rgba(0,0,0,0.1); padding: 20px; border-radius: 12px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.05); }
        .filter-form { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; align-items: flex-end; }
        .filter-form label { display: block; margin-bottom: 6px; color: var(--text-muted); font-size: 12px; }
        .filter-form input, .filter-form select { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white; font-size: 13px; }
        .pagination { display: flex; gap: 5px; justify-content: center; margin-top: 20px; }
        .pagination a, .pagination span { padding: 8px 12px; background: rgba(255,255,255,0.05); color: white; text-decoration: none; border-radius: 6px; font-size: 14px; }
        .pagination a.active { background: var(--accent-blue); }
        .pagination a:hover:not(.active) { background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <h1><i class="fa-solid fa-file-invoice-dollar" style="color: var(--accent-blue);"></i> Account Statement</h1>
                <p>Full history of deposits, withdrawals and transfers.</p>
            </div>
        </header>

        <!-- Account info -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
            <div class="stat-card">
                <div style="color: var(--text-muted); font-size: 14px;">Account Number</div>
                <div style="font-size: 20px; font-weight: bold; margin-top: 10px;"><?= $account['account_number'] ?></div>
            </div>
            <div class="stat-card">
                <div style="color: var(--text-muted); font-size: 14px;">Balance</div>
                <div class="balance-amount"><?= formatCurrency($account['balance']) ?></div>
            </div>
            <div class="stat-card">
                <div style="color: var(--text-muted); font-size: 14px;">Status</div>
                <div style="margin-top: 12px;"><span class="badge-status badge-<?= $account['status'] ?>"><?= ucfirst($account['status']) ?></span></div>
            </div>
        </div>

        <div class="filter-card">
            <form method="GET" class="filter-form">
                <div>
                    <label>Reference No</label>
                    <input type="text" name="txn_id" value="<?= htmlspecialchars($f_txn_id) ?>" placeholder="Search ID...">
                </div>
                <div>
                    <label>Transaction Type</label>
                    <select name="type">
                        <option value="all">All Types</option>
                        <option value="deposit" <?= $f_type==='deposit'?'selected':'' ?>>Deposit</option>
                        <option value="withdraw" <?= $f_type==='withdraw'?'selected':'' ?>>Withdrawal</option>
                        <option value="transfer_out" <?= $f_type==='transfer_out'?'selected':'' ?>>Sent</option>
                        <option value="transfer_in" <?= $f_type==='transfer_in'?'selected':'' ?>>Received</option>
                    </select>
                </div>
                <div>
                    <label>Cash Flow</label>
                    <select name="flow">
                        <option value="">All Flows</option>
                        <option value="credit" <?= $f_flow==='credit'?'selected':'' ?>>Credits Only</option>
                        <option value="debit" <?= $f_flow==='debit'?'selected':'' ?>>Debits Only</option>
                    </select>
                </div>
                <div>
                    <label>Min Amount</label>
                    <input type="number" step="0.01" name="min_amount" value="<?= htmlspecialchars($f_min) ?>">
                </div>
                <div>
                    <label>Max Amount</label>
                    <input type="number" step="0.01" name="max_amount" value="<?= htmlspecialchars($f_max) ?>">
                </div>
                <div>
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($f_start) ?>">
                </div>
                <div>
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($f_end) ?>">
                </div>
                <div>
                    <label>Sort By</label>
                    <select name="sort">
                        <option value="newest" <?= $f_sort==='newest'?'selected':'' ?>>Newest First</option>
                        <option value="oldest" <?= $f_sort==='oldest'?'selected':'' ?>>Oldest First</option>
                        <option value="amt_high" <?= $f_sort==='amt_high'?'selected':'' ?>>Highest Amount</option>
                        <option value="amt_low" <?= $f_sort==='amt_low'?'selected':'' ?>>Lowest Amount</option>
                    </select>
                </div>
                <div style="display:flex; gap:10px; align-items:flex-end; grid-column: 1 / -1;">
                    <button type="submit" class="btn" style="padding: 10px 20px;">Apply Filters</button>
                    <a href="statement.php" class="btn" style="background: rgba(255,255,255,0.1); padding: 10px 20px;">Clear</a>
                </div>
            </form>
        </div>

        <!-- Quick Filters & Export -->
        <div class="filter-row" style="display: flex; justify-content: space-between; align-items: center; margin-top:-10px;">
            <div>
                <!-- Retaining these as quick-links that reset other filters for convenience -->
                <a href="?type=all"          class="filter-chip <?= $f_type==='all' && empty($_GET['flow']) && empty($_GET['txn_id'])?'active':'' ?>">All</a>
                <a href="?type=deposit"      class="filter-chip <?= $f_type==='deposit'?'active':'' ?>">Deposits</a>
                <a href="?type=withdraw"     class="filter-chip <?= $f_type==='withdraw'?'active':'' ?>">Withdrawals</a>
                <a href="?type=transfer_out" class="filter-chip <?= $f_type==='transfer_out'?'active':'' ?>">Sent</a>
                <a href="?type=transfer_in"  class="filter-chip <?= $f_type==='transfer_in'?'active':'' ?>">Received</a>
            </div>
            <div>
                <a href="<?= $exportPdfUrl ?>" target="_blank" class="btn" style="background: var(--accent-blue); color: white; padding: 8px 15px; text-decoration: none; border-radius: 20px; font-size: 13px; margin-right: 5px;">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a href="<?= $exportCsvUrl ?>" class="btn" style="background: #27ae60; color: white; padding: 8px 15px; text-decoration: none; border-radius: 20px; font-size: 13px;">
                    <i class="fa-solid fa-file-csv"></i> CSV
                </a>
            </div>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Reference No</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance After</th>
                        <th>Note</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php $count=0; while ($txn = mysqli_fetch_assoc($transactions)): $count++; $meta = txnMeta($txn['type']); ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($txn['reference_no']) ?></div>
                            <div style="font-size:12px; margin-top:5px;">
                                <a href="receipt.php?id=<?= $txn['transaction_id'] ?>" target="_blank" style="color:var(--accent-blue); text-decoration:none;"><i class="fa-solid fa-file-pdf"></i> Receipt</a>
                            </div>
                        </td>
                        <td>
                            <span class="txn-type <?= $meta['class'] ?>">
                                <i class="fa-solid <?= $meta['icon'] ?>"></i> <?= $meta['label'] ?>
                            </span>
                        </td>
                        <td class="<?= $meta['class'] ?>" style="font-weight: 600;">
                            <?= $meta['sign'] ?><?= formatCurrency($txn['amount']) ?>
                        </td>
                        <td><?= formatCurrency($txn['balance_after']) ?></td>
                        <td><?= htmlspecialchars($txn['note'] ?: '—') ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($txn['created_at'])) ?></td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($count === 0): ?>
                    <tr><td colspan="6" class="empty-state">No transactions match your filters.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php 
                $qs = $_GET;
                unset($qs['page']); 
                $base_qs = http_build_query($qs);
                $base_qs = $base_qs ? '&'.$base_qs : '';
                
                if ($page > 1) echo '<a href="?page='.($page-1).$base_qs.'">&laquo; Prev</a>';
                
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++) {
                    $active_class = $i === $page ? 'active' : '';
                    echo '<a href="?page='.$i.$base_qs.'" class="'.$active_class.'">'.$i.'</a>';
                }
                
                if ($page < $total_pages) echo '<a href="?page='.($page+1).$base_qs.'">Next &raquo;</a>';
            ?>
        </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>
