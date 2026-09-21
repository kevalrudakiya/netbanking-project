<?php
require 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle'])) {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        die("Invalid CSRF token.");
    }
    $toggleId = (int) $_POST['toggle'];
    $stmt = mysqli_prepare($conn, "UPDATE users SET status=IF(status='active','blocked','active') WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, 'i', $toggleId);
    mysqli_stmt_execute($stmt);
    logAudit($conn, 'admin', $_SESSION['admin_id'], 'admin_user_toggle', "Toggled status for user ID: $toggleId");
    
    // Preserve filters on redirect
    $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
    header("Location: users.php" . $qs); 
    exit();
}

// Pagination
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Filters
$f_name = trim($_GET['name'] ?? '');
$f_user = trim($_GET['username'] ?? '');
$f_email = trim($_GET['email'] ?? '');
$f_phone = trim($_GET['phone'] ?? '');
$f_status = trim($_GET['status'] ?? '');
$f_start = trim($_GET['start_date'] ?? '');
$f_end = trim($_GET['end_date'] ?? '');
$f_sort = trim($_GET['sort'] ?? 'newest');

$where = ["1=1"];
$params = [];
$types = "";

if ($f_name !== '') {
    $where[] = "u.full_name LIKE ?";
    $params[] = "%$f_name%"; $types .= "s";
}
if ($f_user !== '') {
    $where[] = "u.username LIKE ?";
    $params[] = "%$f_user%"; $types .= "s";
}
if ($f_email !== '') {
    $where[] = "u.email LIKE ?";
    $params[] = "%$f_email%"; $types .= "s";
}
if ($f_phone !== '') {
    $where[] = "u.phone LIKE ?";
    $params[] = "%$f_phone%"; $types .= "s";
}
if ($f_status !== '') {
    $where[] = "u.status = ?";
    $params[] = $f_status; $types .= "s";
}
if ($f_start !== '') {
    $where[] = "DATE(u.created_at) >= ?";
    $params[] = $f_start; $types .= "s";
}
if ($f_end !== '') {
    $where[] = "DATE(u.created_at) <= ?";
    $params[] = $f_end; $types .= "s";
}

$order_by = "u.user_id DESC";
if ($f_sort === 'oldest') $order_by = "u.user_id ASC";
if ($f_sort === 'name_asc') $order_by = "u.full_name ASC";
if ($f_sort === 'name_desc') $order_by = "u.full_name DESC";

$where_clause = implode(" AND ", $where);

// Base Query
$base_sql = "
    FROM users u
    LEFT JOIN accounts a ON u.user_id = a.user_id
    WHERE $where_clause
";

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $sql = "SELECT u.user_id, u.full_name, u.username, u.email, u.phone, u.status, a.account_number, u.created_at " . $base_sql . " ORDER BY " . $order_by;
    $stmt = mysqli_prepare($conn, $sql);
    if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    exportToCsv('Users_' . date('Ymd_His') . '.csv', mysqli_stmt_get_result($stmt));
}

// Count total for pagination
$count_sql = "SELECT COUNT(u.user_id) as c " . $base_sql;
$stmt_c = mysqli_prepare($conn, $count_sql);
if ($types) mysqli_stmt_bind_param($stmt_c, $types, ...$params);
mysqli_stmt_execute($stmt_c);
$total_rows = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_c))['c'];
$total_pages = ceil($total_rows / $limit);

// Fetch data
$sql = "SELECT u.user_id, u.full_name, u.username, u.email, u.phone, u.status, u.created_at, a.account_number " . $base_sql . " ORDER BY " . $order_by . " LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
$types_fetch = $types . "ii";
$params_fetch = $params;
$params_fetch[] = $limit;
$params_fetch[] = $offset;
if ($types_fetch) mysqli_stmt_bind_param($stmt, $types_fetch, ...$params_fetch);
mysqli_stmt_execute($stmt);
$r = mysqli_stmt_get_result($stmt);

$exportUrl = '?' . http_build_query(array_merge($_GET, ['export' => 'csv']));
$active = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - <?= APP_NAME ?></title>
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
        .badge-active { background: rgba(46, 204, 113, 0.2); color: #2ecc71; }
        .badge-blocked { background: rgba(231, 76, 60, 0.2); color: #e74c3c; }
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
                <h1><i class="fa-solid fa-users" style="color: var(--accent-blue);"></i> Users</h1>
                <p>Advanced search and management for platform customers.</p>
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
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($f_name) ?>">
                </div>
                <div>
                    <label>Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($f_user) ?>">
                </div>
                <div>
                    <label>Email</label>
                    <input type="text" name="email" value="<?= htmlspecialchars($f_email) ?>">
                </div>
                <div>
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($f_phone) ?>">
                </div>
                <div>
                    <label>Status</label>
                    <select name="status">
                        <option value="">All</option>
                        <option value="active" <?= $f_status==='active'?'selected':'' ?>>Active</option>
                        <option value="blocked" <?= $f_status==='blocked'?'selected':'' ?>>Blocked</option>
                    </select>
                </div>
                <div>
                    <label>Reg. Start Date</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($f_start) ?>">
                </div>
                <div>
                    <label>Reg. End Date</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($f_end) ?>">
                </div>
                <div>
                    <label>Sort By</label>
                    <select name="sort">
                        <option value="newest" <?= $f_sort==='newest'?'selected':'' ?>>Newest First</option>
                        <option value="oldest" <?= $f_sort==='oldest'?'selected':'' ?>>Oldest First</option>
                        <option value="name_asc" <?= $f_sort==='name_asc'?'selected':'' ?>>Name (A-Z)</option>
                        <option value="name_desc" <?= $f_sort==='name_desc'?'selected':'' ?>>Name (Z-A)</option>
                    </select>
                </div>
                <div style="display:flex; gap:10px; align-items:flex-end;">
                    <button type="submit" class="btn" style="flex:1;">Search</button>
                    <a href="users.php" class="btn" style="background: rgba(255,255,255,0.1); padding: 10px;">Clear</a>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Contact</th>
                        <th>Account</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php $count = 0; while ($u = mysqli_fetch_assoc($r)): $count++; ?>
                    <tr>
                        <td><?= $u['user_id'] ?></td>
                        <td>
                            <div style="font-weight:600;"><?= htmlspecialchars($u['full_name']) ?></div>
                            <div style="color:var(--text-muted); font-size:13px;">@<?= htmlspecialchars($u['username']) ?></div>
                        </td>
                        <td>
                            <div style="font-size:13px;"><?= htmlspecialchars($u['email']) ?></div>
                            <div style="font-size:13px; color:var(--text-muted);"><?= htmlspecialchars($u['phone']) ?></div>
                        </td>
                        <td><?= $u['account_number'] ? htmlspecialchars($u['account_number']) : '<span style="color:#777">None</span>' ?></td>
                        <td><span class="badge-status badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
                        <td style="font-size:13px;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <form method="POST" style="margin:0;" onsubmit="return confirm('Toggle status for this user?');">
                                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                <input type="hidden" name="toggle" value="<?= $u['user_id'] ?>">
                                <button type="submit" class="btn-action">
                                    <i class="fa-solid <?= $u['status']==='active' ? 'fa-ban' : 'fa-check' ?>"></i> 
                                    <?= $u['status']==='active' ? 'Block' : 'Unblock' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($count === 0): ?>
                    <tr><td colspan="7" class="empty-state">No users match your filters.</td></tr>
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
