<?php
require 'auth.php';
$result = mysqli_query($conn, "SELECT * FROM password_reset_requests ORDER BY request_id DESC");

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    exportToCsv('PasswordRequests_' . date('Ymd_His') . '.csv', $result);
}

$exportUrl = '?' . http_build_query(array_merge($_GET, ['export' => 'csv']));
$active = 'requests';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Requests - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/premium-theme.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="dashboard-container">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><i class="fa-solid fa-key" style="color: var(--accent-blue);"></i> Password Reset Requests</h1>
                <p>Manage user password reset requests.</p>
            </div>
            <div>
                <a href="<?= $exportUrl ?>" class="btn" style="background: #27ae60; color: white; padding: 8px 15px; text-decoration: none; border-radius: 20px; font-size: 13px;">
                    <i class="fa-solid fa-file-csv"></i> Export CSV
                </a>
            </div>
        </header>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Account Number</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php $count = 0; while ($row = mysqli_fetch_assoc($result)): $count++; ?>
                    <tr>
                        <td><?= $row['request_id'] ?></td>
                        <td><?= $row['user_id'] ?></td>
                        <td><?= htmlspecialchars($row['account_number']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td>
                            <span class="badge-status badge-<?= $row['status'] === 'approved' ? 'active' : ($row['status'] === 'rejected' ? 'closed' : 'frozen') ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['status'] === 'pending'): ?>
                                <a class="filter-chip active" href="set_password.php?id=<?= $row['request_id'] ?>">Set Password</a>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 13px;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($count === 0): ?>
                    <tr><td colspan="6" class="empty-state">No password reset requests</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>
