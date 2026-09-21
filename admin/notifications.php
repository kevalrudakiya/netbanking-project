<?php
require 'auth.php';
require_once '../includes/functions.php';

$admin_id = $_SESSION['admin_id'];
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid security token.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'mark_read') {
            $id = (int)$_POST['id'];
            $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read=1 WHERE id=? AND user_id=? AND type='admin'");
            mysqli_stmt_bind_param($stmt, 'ii', $id, $admin_id);
            mysqli_stmt_execute($stmt);
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = mysqli_prepare($conn, "DELETE FROM notifications WHERE id=? AND user_id=? AND type='admin'");
            mysqli_stmt_bind_param($stmt, 'ii', $id, $admin_id);
            mysqli_stmt_execute($stmt);
            $success = "Notification deleted.";
        } elseif ($action === 'mark_all_read') {
            $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read=1 WHERE user_id=? AND type='admin'");
            mysqli_stmt_bind_param($stmt, 'i', $admin_id);
            mysqli_stmt_execute($stmt);
            $success = "All notifications marked as read.";
        }
    }
}

// Fetch
$stmt = mysqli_prepare($conn, "SELECT * FROM notifications WHERE type='admin' AND (user_id=? OR user_id=0) ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, 'i', $admin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$active = ''; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/premium-theme.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .notif-card {
            background: rgba(18, 18, 18, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-left: 4px solid var(--accent-blue);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notif-card.unread {
            background: rgba(255, 255, 255, 0.05);
            border-left-color: #ff3e6c;
        }
        .notif-content h4 { margin: 0 0 5px 0; color: #fff; font-size: 16px; }
        .notif-content p { margin: 0 0 5px 0; color: var(--text-muted); font-size: 14px; }
        .notif-meta { font-size: 11px; color: rgba(255,255,255,0.3); }
        .notif-actions { display: flex; gap: 10px; }
        .btn-icon { background: rgba(255,255,255,0.1); border: none; color: white; width: 35px; height: 35px; border-radius: 6px; cursor: pointer; transition: 0.2s; }
        .btn-icon:hover { background: rgba(255,255,255,0.2); }
        .btn-danger { color: #e74c3c; }
        .badge-broadcast { background: #9b59b6; color: white; font-size: 10px; padding: 2px 6px; border-radius: 4px; vertical-align: middle; margin-left: 10px; text-transform: uppercase;}
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h1><i class="fa-regular fa-bell" style="color: var(--accent-blue);"></i> Admin Notifications</h1>
                <p>System alerts and security requests.</p>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn" style="background: rgba(255,255,255,0.1); color: white;"><i class="fa-solid fa-check-double"></i> Mark All as Read</button>
            </form>
        </header>

        <?php if ($error): ?>
            <div class="alert-flash danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-flash success"><?= $success ?></div>
        <?php endif; ?>

        <?php $count = 0; while($n = mysqli_fetch_assoc($result)): $count++; ?>
            <div class="notif-card <?= $n['is_read'] ? '' : 'unread' ?>">
                <div class="notif-content">
                    <h4>
                        <?= htmlspecialchars($n['title']) ?>
                        <?php if ($n['user_id'] == 0): ?>
                            <span class="badge-broadcast">System Wide</span>
                        <?php endif; ?>
                    </h4>
                    <p><?= htmlspecialchars($n['message']) ?></p>
                    <div class="notif-meta"><i class="fa-regular fa-clock"></i> <?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></div>
                </div>
                <div class="notif-actions">
                    <?php if ($n['user_id'] != 0): ?>
                        <?php if (!$n['is_read']): ?>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                            <input type="hidden" name="action" value="mark_read">
                            <input type="hidden" name="id" value="<?= $n['id'] ?>">
                            <button type="submit" class="btn-icon" title="Mark as Read"><i class="fa-solid fa-check"></i></button>
                        </form>
                        <?php endif; ?>
                        
                        <form method="POST" style="margin:0;" onsubmit="return confirm('Delete notification?');">
                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $n['id'] ?>">
                            <button type="submit" class="btn-icon btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>

        <?php if($count === 0): ?>
            <div class="empty-state" style="text-align:center; padding: 40px; background: rgba(255,255,255,0.02); border-radius:12px; color: var(--text-muted);">
                <i class="fa-regular fa-bell-slash" style="font-size: 40px; opacity:0.5; margin-bottom:15px;"></i>
                <p>No admin notifications.</p>
            </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>
