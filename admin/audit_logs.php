<?php
require 'auth.php';

// Pagination settings
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

// Filters
$search = $_GET['search'] ?? '';
$date_start = $_GET['date_start'] ?? '';
$date_end = $_GET['date_end'] ?? '';
$actor_type = $_GET['actor_type'] ?? '';
$action_filter = $_GET['action'] ?? '';

// Build query
$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(details LIKE ? OR ip_address LIKE ? OR user_agent LIKE ?)";
    $likeSearch = "%" . $search . "%";
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $types .= 'sss';
}

if ($date_start !== '') {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $date_start;
    $types .= 's';
}

if ($date_end !== '') {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $date_end;
    $types .= 's';
}

if ($actor_type !== '') {
    $where[] = "actor_type = ?";
    $params[] = $actor_type;
    $types .= 's';
}

if ($action_filter !== '') {
    $where[] = "action = ?";
    $params[] = $action_filter;
    $types .= 's';
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get total for pagination
$count_sql = "SELECT COUNT(*) as total FROM audit_logs $whereClause";
$count_stmt = mysqli_prepare($conn, $count_sql);
if ($types !== '') {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$total_rows = mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];
$total_pages = ceil($total_rows / $limit);

// Get results
$sql = "SELECT * FROM audit_logs $whereClause ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    exportToCsv('AuditLogs_' . date('Ymd_His') . '.csv', $result);
}

$exportUrl = '?' . http_build_query(array_merge($_GET, ['export' => 'csv']));

// Fetch unique actions for dropdown
$actions_res = mysqli_query($conn, "SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");

$active = 'audit';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/premium-theme.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-card {
            background: rgba(18, 18, 18, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            align-items: end;
        }
        .log-meta {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .actor-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .actor-user { background: rgba(52, 152, 219, 0.15); color: #3498db; }
        .actor-admin { background: rgba(155, 89, 182, 0.15); color: #9b59b6; }
        
        .pagination {
            display: flex;
            gap: 5px;
            margin-top: 20px;
            justify-content: center;
        }
        .page-btn {
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-color);
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
        }
        .page-btn:hover { background: rgba(255, 255, 255, 0.1); }
        .page-btn.active { background: var(--accent-blue); border-color: var(--accent-blue); }
        .details-trigger { cursor: pointer; color: var(--accent-blue); text-decoration: underline; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><i class="fa-solid fa-shield-halved" style="color: var(--accent-blue);"></i> Audit Logs</h1>
                <p>System-wide security and action logging.</p>
            </div>
            <div>
                <a href="<?= $exportUrl ?>" class="btn" style="background: #27ae60; color: white; padding: 8px 15px; text-decoration: none; border-radius: 20px; font-size: 13px;">
                    <i class="fa-solid fa-file-csv"></i> Export CSV
                </a>
            </div>
        </header>

        <div class="filter-card">
            <form method="GET" class="filter-grid">
                <div class="form-group mb-0">
                    <label>Search Keyword</label>
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="IP, Agent, or Details">
                </div>
                <div class="form-group mb-0">
                    <label>Start Date</label>
                    <input type="date" name="date_start" class="form-control" value="<?= htmlspecialchars($date_start) ?>">
                </div>
                <div class="form-group mb-0">
                    <label>End Date</label>
                    <input type="date" name="date_end" class="form-control" value="<?= htmlspecialchars($date_end) ?>">
                </div>
                <div class="form-group mb-0">
                    <label>Actor</label>
                    <select name="actor_type" class="form-control">
                        <option value="">All Types</option>
                        <option value="user" <?= $actor_type === 'user' ? 'selected' : '' ?>>Users</option>
                        <option value="admin" <?= $actor_type === 'admin' ? 'selected' : '' ?>>Admins</option>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label>Action</label>
                    <select name="action" class="form-control">
                        <option value="">All Actions</option>
                        <?php while($a = mysqli_fetch_assoc($actions_res)): ?>
                            <option value="<?= htmlspecialchars($a['action']) ?>" <?= $action_filter === $a['action'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['action']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <button type="submit" class="btn btn-primary" style="width: 100%; height: 42px;"><i class="fa-solid fa-filter"></i> Filter</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Details & Network</th>
                    </tr>
                </thead>
                <tbody>
                <?php $count = 0; while ($log = mysqli_fetch_assoc($result)): $count++; ?>
                    <tr>
                        <td style="white-space: nowrap;">
                            <?= date('d M Y, h:i A', strtotime($log['created_at'])) ?>
                        </td>
                        <td>
                            <span class="actor-badge <?= $log['actor_type'] === 'admin' ? 'actor-admin' : 'actor-user' ?>">
                                <?= htmlspecialchars($log['actor_type']) ?>
                            </span>
                            <div class="log-meta">ID: <?= $log['actor_id'] ?? 'N/A' ?></div>
                        </td>
                        <td>
                            <strong style="font-family: monospace; font-size: 13px;"><?= htmlspecialchars($log['action']) ?></strong>
                            <?php if ($log['related_account_id']): ?>
                            <div class="log-meta">Acc ID: <?= $log['related_account_id'] ?></div>
                            <?php endif; ?>
                            <?php if ($log['related_transaction_id']): ?>
                            <div class="log-meta">Txn ID: <?= $log['related_transaction_id'] ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-size: 13px; margin-bottom: 5px;"><?= htmlspecialchars($log['details'] ?? 'No details provided') ?></div>
                            <div class="log-meta">
                                <i class="fa-solid fa-network-wired"></i> <?= htmlspecialchars($log['ip_address'] ?? 'Unknown IP') ?>
                                <span class="details-trigger" onclick="alert('User Agent:\n<?= htmlspecialchars(addslashes($log['user_agent'] ?? '')) ?>')" style="margin-left: 10px;">[View Agent]</span>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($count === 0): ?>
                    <tr><td colspan="4" class="empty-state">No audit logs matched your criteria</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&date_start=<?= urlencode($date_start) ?>&date_end=<?= urlencode($date_end) ?>&actor_type=<?= urlencode($actor_type) ?>&action=<?= urlencode($action_filter) ?>" 
                   class="page-btn <?= $page === $i ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>
