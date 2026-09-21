<?php
require 'auth.php';

// Pagination
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Filters
$f_txn_id = trim($_GET['txn_id'] ?? '');
$f_acc_no = trim($_GET['account_number'] ?? '');
$f_username = trim($_GET['username'] ?? '');
$f_name = trim($_GET['name'] ?? '');
$f_type = trim($_GET['type'] ?? '');
$f_min = trim($_GET['min_amount'] ?? '');
$f_max = trim($_GET['max_amount'] ?? '');
$f_start = trim($_GET['start_date'] ?? '');
$f_end = trim($_GET['end_date'] ?? '');
$f_sort = trim($_GET['sort'] ?? 'newest');

$where = ["1=1"];
$params = [];
$types = "";

if ($f_txn_id !== '') {
    $where[] = "t.reference_no LIKE ?";
    $params[] = "%$f_txn_id%"; $types .= "s";
}
if ($f_acc_no !== '') {
    $where[] = "a.account_number LIKE ?";
    $params[] = "%$f_acc_no%"; $types .= "s";
}
if ($f_username !== '') {
    $where[] = "u.username LIKE ?";
    $params[] = "%$f_username%"; $types .= "s";
}
if ($f_name !== '') {
    $where[] = "u.full_name LIKE ?";
    $params[] = "%$f_name%"; $types .= "s";
}
if ($f_type !== '') {
    $where[] = "t.type = ?";
    $params[] = $f_type; $types .= "s";
}
if (is_numeric($f_min)) {
    $where[] = "t.amount >= ?";
    $params[] = $f_min; $types .= "d";
}
if (is_numeric($f_max)) {
    $where[] = "t.amount <= ?";
    $params[] = $f_max; $types .= "d";
}
if ($f_start !== '') {
    $where[] = "DATE(t.created_at) >= ?";
    $params[] = $f_start; $types .= "s";
}
if ($f_end !== '') {
    $where[] = "DATE(t.created_at) <= ?";
    $params[] = $f_end; $types .= "s";
}

$order_by = "t.transaction_id DESC";
if ($f_sort === 'oldest') $order_by = "t.transaction_id ASC";
if ($f_sort === 'amt_high') $order_by = "t.amount DESC";
if ($f_sort === 'amt_low') $order_by = "t.amount ASC";

$where_clause = implode(" AND ", $where);

// Base Query
$base_sql = "
    FROM transactions t
    JOIN accounts a ON t.account_id = a.account_id
    JOIN users u ON a.user_id = u.user_id
    WHERE $where_clause
";

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $sql = "SELECT t.transaction_id, t.reference_no, u.full_name, u.username, a.account_number, t.type, t.amount, t.balance_after, t.created_at " . $base_sql . " ORDER BY " . $order_by;
    $stmt = mysqli_prepare($conn, $sql);
    if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    exportToCsv('GlobalTransactions_' . date('Ymd_His') . '.csv', mysqli_stmt_get_result($stmt));
}

// Count total for pagination
$count_sql = "SELECT COUNT(*) as c " . $base_sql;
$stmt_c = mysqli_prepare($conn, $count_sql);
if ($types) mysqli_stmt_bind_param($stmt_c, $types, ...$params);
mysqli_stmt_execute($stmt_c);
$total_rows = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_c))['c'];
$total_pages = ceil($total_rows / $limit);

// Fetch data
$sql = "SELECT t.*, u.full_name, u.username, a.account_number " . $base_sql . " ORDER BY " . $order_by . " LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
$types_fetch = $types . "ii";
$params_fetch = $params;
$params_fetch[] = $limit;
$params_fetch[] = $offset;
if ($types_fetch) mysqli_stmt_bind_param($stmt, $types_fetch, ...$params_fetch);
mysqli_stmt_execute($stmt);
$r = mysqli_stmt_get_result($stmt);


$exportUrl = '?' . http_build_query(array_merge($_GET, ['export' => 'csv']));

function txnMeta($type) {
    switch ($type) {
        case 'deposit':      return ['label' => 'Deposit',      'sign' => '+', 'class' => 'status-deposit',  'icon' => 'fa-arrow-down'];
        case 'withdraw':     return ['label' => 'Withdrawal',   'sign' => '-', 'class' => 'status-withdraw', 'icon' => 'fa-arrow-up'];
        case 'transfer_in':  return ['label' => 'Transfer In',  'sign' => '+', 'class' => 'status-deposit',  'icon' => 'fa-arrow-down'];
        case 'transfer_out': return ['label' => 'Transfer Out', 'sign' => '-', 'class' => 'status-withdraw', 'icon' => 'fa-arrow-up'];
        default:             return ['label' => ucfirst($type), 'sign' => '',  'class' => '',                'icon' => 'fa-circle'];
    }
}

$active = 'transactions';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/premium-theme.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-card { background: var(--surface-light); padding: 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid rgba(255,255,255,0.05); }
        .filter-form { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; align-items: flex-end; }
        .filter-form label { display: block; margin-bottom: 8px; color: var(--text-muted); font-size: 13px; }
        .filter-form input, .filter-form select { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white; }
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
        <header class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><i class="fa-solid fa-money-bill-transfer" style="color: var(--accent-blue);"></i> Global Transactions</h1>
                <p>Advanced search and filtering for platform transactions.</p>
            </div>
            <div>
                <a href="<?= $exportUrl ?>" class="btn" style="background: #27ae60; color: white; padding: 8px 15px; text-decoration: none; border-radius: 20px; font-size: 13px;">
                    <i class="fa-solid fa-file-csv"></i> Export CSV
                </a>
            </div>
        </header>

        <div class="filter-card">
            <form method="GET" class="filter-form">
                <div>
                    <label>Reference No</label>
                    <input type="text" name="txn_id" value="<?= htmlspecialchars($f_txn_id) ?>" placeholder="TXN...">
                </div>
                <div>
                    <label>Account Number</label>
                    <input type="text" name="account_number" value="<?= htmlspecialchars($f_acc_no) ?>">
                </div>
                <div>
                    <label>Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($f_username) ?>">
                </div>
                <div>
                    <label>Type</label>
                    <select name="type">
                        <option value="">All Types</option>
                        <option value="deposit" <?= $f_type==='deposit'?'selected':'' ?>>Deposit</option>
                        <option value="withdraw" <?= $f_type==='withdraw'?'selected':'' ?>>Withdrawal</option>
                        <option value="transfer_out" <?= $f_type==='transfer_out'?'selected':'' ?>>Transfer Out</option>
                        <option value="transfer_in" <?= $f_type==='transfer_in'?'selected':'' ?>>Transfer In</option>
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
                <div style="display:flex; gap:10px; align-items:flex-end;">
                    <button type="submit" class="btn" style="flex:1;">Search</button>
                    <a href="transactions.php" class="btn" style="background: rgba(255,255,255,0.1); padding: 10px;">Clear</a>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>User</th>
                        <th>Account</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php $count = 0; while ($t = mysqli_fetch_assoc($r)): $count++; $meta = txnMeta($t['type']); ?>
                    <tr>
                        <td><?= htmlspecialchars($t['reference_no']) ?></td>
                        <td><?= htmlspecialchars($t['full_name']) ?><br><small style="color:var(--text-muted)">@<?= htmlspecialchars($t['username']) ?></small></td>
                        <td><?= htmlspecialchars($t['account_number']) ?></td>
                        <td>
                            <span class="txn-type <?= $meta['class'] ?>">
                                <i class="fa-solid <?= $meta['icon'] ?>"></i> <?= $meta['label'] ?>
                            </span>
                        </td>
                        <td class="<?= $meta['class'] ?>" style="font-weight:600;"><?= $meta['sign'] ?><?= formatCurrency($t['amount']) ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($t['created_at'])) ?></td>
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
