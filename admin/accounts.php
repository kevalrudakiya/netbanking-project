<?php
require 'auth.php';

// Handle Actions (Freeze, Unfreeze, Close)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['account_id'])) {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        die("Invalid CSRF token.");
    }

    $action = $_POST['action'];
    $acc_id = (int)$_POST['account_id'];
    
    // Validate current status
    $s_stmt = mysqli_prepare($conn, "SELECT status, account_number FROM accounts WHERE account_id=?");
    mysqli_stmt_bind_param($s_stmt, 'i', $acc_id);
    mysqli_stmt_execute($s_stmt);
    $acc_data = mysqli_fetch_assoc(mysqli_stmt_get_result($s_stmt));

    if ($acc_data) {
        $curr_status = $acc_data['status'];
        $acc_num = $acc_data['account_number'];
        $new_status = null;

        if ($action === 'freeze' && $curr_status === 'active') {
            $new_status = 'frozen';
        } elseif ($action === 'unfreeze' && $curr_status === 'frozen') {
            $new_status = 'active';
        } elseif ($action === 'close' && $curr_status !== 'closed') {
            $new_status = 'closed';
        }

        if ($new_status) {
            $u_stmt = mysqli_prepare($conn, "UPDATE accounts SET status=? WHERE account_id=?");
            mysqli_stmt_bind_param($u_stmt, 'si', $new_status, $acc_id);
            mysqli_stmt_execute($u_stmt);
            logAudit($conn, 'admin', $_SESSION['admin_id'], 'admin_account_status_change', "Changed account $acc_num status from $curr_status to $new_status", $acc_id);
        }
    }
    
    // Preserve filters on redirect
    $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
    header("Location: accounts.php" . $qs);
    exit();
}

// Pagination
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Filters
$f_acc_no = trim($_GET['account_number'] ?? '');
$f_owner = trim($_GET['owner'] ?? '');
$f_type = trim($_GET['account_type'] ?? '');
$f_status = trim($_GET['status'] ?? '');
$f_min = trim($_GET['min_balance'] ?? '');
$f_max = trim($_GET['max_balance'] ?? '');
$f_sort = trim($_GET['sort'] ?? 'newest');

$where = ["1=1"];
$params = [];
$types = "";

if ($f_acc_no !== '') {
    $where[] = "a.account_number LIKE ?";
    $params[] = "%$f_acc_no%"; $types .= "s";
}
if ($f_owner !== '') {
    $where[] = "(u.full_name LIKE ? OR u.username LIKE ?)";
    $params[] = "%$f_owner%"; $params[] = "%$f_owner%"; $types .= "ss";
}
if ($f_type !== '') {
    $where[] = "a.account_type = ?";
    $params[] = $f_type; $types .= "s";
}
if ($f_status !== '') {
    $where[] = "a.status = ?";
    $params[] = $f_status; $types .= "s";
}
if (is_numeric($f_min)) {
    $where[] = "a.balance >= ?";
    $params[] = $f_min; $types .= "d";
}
if (is_numeric($f_max)) {
    $where[] = "a.balance <= ?";
    $params[] = $f_max; $types .= "d";
}

$order_by = "a.account_id DESC";
if ($f_sort === 'oldest') $order_by = "a.account_id ASC";
if ($f_sort === 'bal_high') $order_by = "a.balance DESC";
if ($f_sort === 'bal_low') $order_by = "a.balance ASC";

$where_clause = implode(" AND ", $where);

// Base Query
$base_sql = "
    FROM accounts a
    JOIN users u ON a.user_id = u.user_id
    WHERE $where_clause
";

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $sql = "SELECT a.account_id, a.account_number, u.full_name, u.username, a.account_type, a.balance, a.status, a.created_at " . $base_sql . " ORDER BY " . $order_by;
    $stmt = mysqli_prepare($conn, $sql);
    if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    exportToCsv('Accounts_' . date('Ymd_His') . '.csv', mysqli_stmt_get_result($stmt));
}

// Count total for pagination
$count_sql = "SELECT COUNT(a.account_id) as c " . $base_sql;
$stmt_c = mysqli_prepare($conn, $count_sql);
if ($types) mysqli_stmt_bind_param($stmt_c, $types, ...$params);
mysqli_stmt_execute($stmt_c);
$total_rows = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_c))['c'];
$total_pages = ceil($total_rows / $limit);

// Fetch data
$sql = "SELECT a.*, u.full_name, u.username " . $base_sql . " ORDER BY " . $order_by . " LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
$types_fetch = $types . "ii";
$params_fetch = $params;
$params_fetch[] = $limit;
$params_fetch[] = $offset;
if ($types_fetch) mysqli_stmt_bind_param($stmt, $types_fetch, ...$params_fetch);
mysqli_stmt_execute($stmt);
$r = mysqli_stmt_get_result($stmt);

$exportUrl = '?' . http_build_query(array_merge($_GET, ['export' => 'csv']));
$active = 'accounts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounts - <?= APP_NAME ?></title>
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
        .btn-action { padding: 6px 12px; border-radius: 6px; background: rgba(43,116,255,0.1); color: var(--accent-blue); text-decoration: none; font-size: 13px; font-weight: 600; border: 1px solid rgba(43,116,255,0.3); transition: all 0.2s; cursor:pointer;}
        .btn-action:hover { background: var(--accent-blue); color: white; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><i class="fa-solid fa-wallet" style="color: var(--accent-blue);"></i> Accounts</h1>
                <p>Manage customer bank accounts and statuses.</p>
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
                    <label>Account Number</label>
                    <input type="text" name="account_number" value="<?= htmlspecialchars($f_acc_no) ?>">
                </div>
                <div>
                    <label>Owner (Name/Username)</label>
                    <input type="text" name="owner" value="<?= htmlspecialchars($f_owner) ?>">
                </div>
                <div>
                    <label>Account Type</label>
                    <select name="account_type">
                        <option value="">All Types</option>
                        <option value="savings" <?= $f_type==='savings'?'selected':'' ?>>Savings</option>
                        <option value="current" <?= $f_type==='current'?'selected':'' ?>>Current</option>
                    </select>
                </div>
                <div>
                    <label>Status</label>
                    <select name="status">
                        <option value="">All</option>
                        <option value="active" <?= $f_status==='active'?'selected':'' ?>>Active</option>
                        <option value="frozen" <?= $f_status==='frozen'?'selected':'' ?>>Frozen</option>
                        <option value="closed" <?= $f_status==='closed'?'selected':'' ?>>Closed</option>
                    </select>
                </div>
                <div>
                    <label>Min Balance</label>
                    <input type="number" step="0.01" name="min_balance" value="<?= htmlspecialchars($f_min) ?>">
                </div>
                <div>
                    <label>Max Balance</label>
                    <input type="number" step="0.01" name="max_balance" value="<?= htmlspecialchars($f_max) ?>">
                </div>
                <div>
                    <label>Sort By</label>
                    <select name="sort">
                        <option value="newest" <?= $f_sort==='newest'?'selected':'' ?>>Newest First</option>
                        <option value="oldest" <?= $f_sort==='oldest'?'selected':'' ?>>Oldest First</option>
                        <option value="bal_high" <?= $f_sort==='bal_high'?'selected':'' ?>>Highest Balance</option>
                        <option value="bal_low" <?= $f_sort==='bal_low'?'selected':'' ?>>Lowest Balance</option>
                    </select>
                </div>
                <div style="display:flex; gap:10px; align-items:flex-end;">
                    <button type="submit" class="btn" style="flex:1;">Search</button>
                    <a href="accounts.php" class="btn" style="background: rgba(255,255,255,0.1); padding: 10px;">Clear</a>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Acc No</th>
                        <th>Owner</th>
                        <th>Type</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php $count = 0; while ($a = mysqli_fetch_assoc($r)): $count++; ?>
                    <tr>
                        <td>
                            <div style="font-weight:bold;"><?= $a['account_number'] ?></div>
                            <div style="color:var(--text-muted); font-size:12px; margin-top:3px;">Created: <?= date('d M Y', strtotime($a['created_at'])) ?></div>
                        </td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($a['full_name']) ?></div>
                            <div style="color:var(--text-muted); font-size:13px;">@<?= htmlspecialchars($a['username']) ?></div>
                        </td>
                        <td><?= ucfirst($a['account_type']) ?></td>
                        <td style="font-weight:bold;"><?= formatCurrency($a['balance']) ?></td>
                        <td><span class="badge-status badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a href="account_details.php?id=<?= $a['account_id'] ?>" class="btn-action" title="View"><i class="fa-regular fa-eye"></i></a>
                                
                                <?php if($a['status'] === 'active'): ?>
                                    <form method="POST" style="margin:0;" onsubmit="return confirm('Freeze this account?');">
                                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                        <input type="hidden" name="action" value="freeze">
                                        <input type="hidden" name="account_id" value="<?= $a['account_id'] ?>">
                                        <button type="submit" class="btn-action" style="color:#e67e22; border-color:rgba(230,126,34,0.3); background:rgba(230,126,34,0.1);" title="Freeze"><i class="fa-regular fa-snowflake"></i></button>
                                    </form>
                                <?php elseif($a['status'] === 'frozen'): ?>
                                    <form method="POST" style="margin:0;" onsubmit="return confirm('Unfreeze this account?');">
                                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                        <input type="hidden" name="action" value="unfreeze">
                                        <input type="hidden" name="account_id" value="<?= $a['account_id'] ?>">
                                        <button type="submit" class="btn-action" style="color:#2ecc71; border-color:rgba(46,204,113,0.3); background:rgba(46,204,113,0.1);" title="Unfreeze"><i class="fa-solid fa-fire"></i></button>
                                    </form>
                                <?php endif; ?>

                                <?php if($a['status'] !== 'closed'): ?>
                                    <form method="POST" style="margin:0;" onsubmit="return confirm('WARNING: Close this account? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                        <input type="hidden" name="action" value="close">
                                        <input type="hidden" name="account_id" value="<?= $a['account_id'] ?>">
                                        <button type="submit" class="btn-action" style="color:#e74c3c; border-color:rgba(231,76,60,0.3); background:rgba(231,76,60,0.1);" title="Close Account"><i class="fa-solid fa-ban"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($count === 0): ?>
                    <tr><td colspan="6" class="empty-state">No accounts match your filters.</td></tr>
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
