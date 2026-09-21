<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireUserLogin();

$user_id = $_SESSION['user_id'];
$account = validateAccountStatus($conn, $user_id, 'view');

// Pagination for Login History
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count Total
$stmt_c = mysqli_prepare($conn, "SELECT COUNT(*) as c FROM user_login_history WHERE user_id=?");
mysqli_stmt_bind_param($stmt_c, 'i', $user_id);
mysqli_stmt_execute($stmt_c);
$total_rows = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_c))['c'];
$total_pages = ceil($total_rows / $limit);

// Fetch History
$stmt = mysqli_prepare($conn, "SELECT * FROM user_login_history WHERE user_id=? ORDER BY id DESC LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($stmt, 'iii', $user_id, $limit, $offset);
mysqli_stmt_execute($stmt);
$history = mysqli_stmt_get_result($stmt);

// Fetch Last Successful Login
$last_stmt = mysqli_prepare($conn, "SELECT * FROM user_login_history WHERE user_id=? AND status='success' ORDER BY id DESC LIMIT 1 OFFSET 1"); // Offset 1 to ignore current session
mysqli_stmt_bind_param($last_stmt, 'i', $user_id);
mysqli_stmt_execute($last_stmt);
$last_login = mysqli_fetch_assoc(mysqli_stmt_get_result($last_stmt));

$active = 'security';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Center - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/premium-theme.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .security-grid { display: grid; grid-template-columns: 1fr 350px; gap: 20px; align-items: start; }
        @media (max-width: 992px) { .security-grid { grid-template-columns: 1fr; } }
        .sec-card { background: var(--surface-light); padding: 25px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); }
        .sec-card h3 { font-size: 16px; margin-bottom: 20px; font-weight: 600; color: white; display:flex; align-items:center; gap:10px; }
        
        .shortcut-btn { display: flex; align-items: center; justify-content: space-between; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 10px; text-decoration: none; color: white; margin-bottom: 15px; transition: 0.2s; border: 1px solid rgba(255,255,255,0.05); }
        .shortcut-btn:hover { background: rgba(43,116,255,0.1); border-color: rgba(43,116,255,0.3); }
        .shortcut-btn .icon { width: 40px; height: 40px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--accent-blue); }
        .shortcut-btn .text { flex: 1; margin-left: 15px; }
        .shortcut-btn .text strong { display: block; font-size: 15px; }
        .shortcut-btn .text small { color: var(--text-muted); font-size: 13px; }
        
        .alert-box { background: rgba(231, 76, 60, 0.1); border-left: 4px solid #e74c3c; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-box h4 { color: #e74c3c; margin-bottom: 5px; font-size: 15px; font-weight: 600; }
        .alert-box p { margin: 0; font-size: 13px; color: rgba(255,255,255,0.8); }

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
                <h1><i class="fa-solid fa-shield-halved" style="color: var(--accent-blue);"></i> Security Center</h1>
                <p>Monitor your account access and manage authentication credentials.</p>
            </div>
        </header>

        <div class="security-grid">
            <!-- Left Column: Login History -->
            <div>
                <div class="sec-card">
                    <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Login Activity</h3>
                    
                    <div class="table-container" style="background:transparent; border:none; padding:0; box-shadow:none;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>IP Address</th>
                                    <th>Device / Browser</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $count = 0; while ($log = mysqli_fetch_assoc($history)): $count++; ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600;"><?= date('d M Y', strtotime($log['created_at'])) ?></div>
                                        <div style="font-size:12px; color:var(--text-muted);"><?= date('h:i A', strtotime($log['created_at'])) ?></div>
                                    </td>
                                    <td><i class="fa-solid fa-location-dot" style="color:var(--text-muted);"></i> <?= htmlspecialchars($log['ip_address']) ?></td>
                                    <td style="font-size:13px; color:var(--text-muted); max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($log['user_agent']) ?>">
                                        <?= htmlspecialchars($log['user_agent']) ?>
                                    </td>
                                    <td>
                                        <?php if ($log['status'] === 'success'): ?>
                                            <span class="badge-status badge-active">Success</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-closed">Failed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if ($count === 0): ?>
                                <tr><td colspan="4" class="empty-state">No login history found.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php 
                            if ($page > 1) echo '<a href="?page='.($page-1).'">&laquo; Prev</a>';
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                $active_class = $i === $page ? 'active' : '';
                                echo '<a href="?page='.$i.'" class="'.$active_class.'">'.$i.'</a>';
                            }
                            if ($page < $total_pages) echo '<a href="?page='.($page+1).'">Next &raquo;</a>';
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Shortcuts & Alerts -->
            <div>
                <?php if ($last_login): ?>
                <div class="sec-card" style="margin-bottom:20px;">
                    <h3><i class="fa-solid fa-circle-check" style="color:#2ecc71;"></i> Last Successful Login</h3>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <span style="color:var(--text-muted); font-size:13px;">IP Address</span>
                        <strong><?= htmlspecialchars($last_login['ip_address']) ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--text-muted); font-size:13px;">Time</span>
                        <strong><?= date('d M Y, h:i A', strtotime($last_login['created_at'])) ?></strong>
                    </div>
                </div>
                <?php endif; ?>

                <div class="sec-card">
                    <h3><i class="fa-solid fa-gear"></i> Security Settings</h3>
                    
                    <a href="profile.php" class="shortcut-btn">
                        <div class="icon"><i class="fa-solid fa-key"></i></div>
                        <div class="text">
                            <strong>Change Password</strong>
                            <small>Update your login password securely</small>
                        </div>
                        <i class="fa-solid fa-chevron-right" style="color:var(--text-muted);"></i>
                    </a>

                    <a href="profile.php" class="shortcut-btn">
                        <div class="icon"><i class="fa-solid fa-th-list"></i></div>
                        <div class="text">
                            <strong>Change Transaction PIN</strong>
                            <small>Update your 6-digit transaction PIN</small>
                        </div>
                        <i class="fa-solid fa-chevron-right" style="color:var(--text-muted);"></i>
                    </a>

                    <div class="alert-box">
                        <h4><i class="fa-solid fa-triangle-exclamation"></i> Security Tips</h4>
                        <p>Never share your password or Transaction PIN with anyone. NetBanking will never ask for your credentials over email or phone.</p>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

</body>
</html>
