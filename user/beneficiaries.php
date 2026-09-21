<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireUserLogin();

$user_id = $_SESSION['user_id'];
$account = validateAccountStatus($conn, $user_id, 'financial');

$error = $success = '';
$step = 1;
$verified_account = '';
$verified_name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'verify_account') {
            $to_account = sanitize($_POST['to_account']);
            if (empty($to_account)) {
                $error = "Please enter an account number.";
            } elseif ($to_account === $account['account_number']) {
                $error = "You cannot add your own account as a beneficiary.";
            } else {
                // Check if already a beneficiary
                $chk = mysqli_prepare($conn, "SELECT beneficiary_id FROM beneficiaries WHERE user_id=? AND account_number=?");
                mysqli_stmt_bind_param($chk, 'is', $user_id, $to_account);
                mysqli_stmt_execute($chk);
                if (mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0) {
                    $error = "This account is already in your beneficiaries list.";
                } else {
                    $rx_result = validateReceiverForTransfer($conn, $to_account);
                    if (isset($rx_result['error'])) {
                        $error = $rx_result['error'];
                    } else {
                        // Success! Move to step 2
                        $step = 2;
                        $verified_account = $to_account;
                        $verified_name = $rx_result['user_name'];
                    }
                }
            }
        } elseif ($action === 'add_beneficiary') {
            $to_account = sanitize($_POST['verified_account']);
            $verified_name = sanitize($_POST['verified_name']); // passed hidden for simplicity, but strictly we should re-verify or assume it's correct enough for local caching. We'll re-verify to be perfectly safe.
            $nickname = sanitize($_POST['nickname']);
            $is_favorite = isset($_POST['is_favorite']) ? 1 : 0;
            
            // Re-verify securely
            $rx_result = validateReceiverForTransfer($conn, $to_account);
            if (isset($rx_result['error'])) {
                $error = "Account is no longer valid: " . $rx_result['error'];
            } elseif ($to_account === $account['account_number']) {
                $error = "You cannot add your own account.";
            } else {
                $true_name = $rx_result['user_name'];
                
                $ins = mysqli_prepare($conn, "INSERT INTO beneficiaries (user_id, account_number, beneficiary_name, nickname, is_favorite) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($ins, 'isssi', $user_id, $to_account, $true_name, $nickname, $is_favorite);
                if (mysqli_stmt_execute($ins)) {
                    $success = "Beneficiary added successfully!";
                    logAudit($conn, 'user', $user_id, 'user_add_beneficiary', "Added beneficiary account: $to_account");
                } else {
                    // Might fail on unique constraint if concurrent
                    $error = "Failed to add beneficiary. It may already exist.";
                }
            }
        } elseif ($action === 'delete_beneficiary') {
            $beneficiary_id = (int)$_POST['beneficiary_id'];
            $del = mysqli_prepare($conn, "DELETE FROM beneficiaries WHERE beneficiary_id=? AND user_id=?");
            mysqli_stmt_bind_param($del, 'ii', $beneficiary_id, $user_id);
            mysqli_stmt_execute($del);
            if (mysqli_stmt_affected_rows($del) > 0) {
                $success = "Beneficiary removed.";
                logAudit($conn, 'user', $user_id, 'user_delete_beneficiary', "Removed beneficiary ID: $beneficiary_id");
            } else {
                $error = "Failed to remove beneficiary.";
            }
        } elseif ($action === 'toggle_favorite') {
            $beneficiary_id = (int)$_POST['beneficiary_id'];
            $new_val = (int)$_POST['new_status'];
            $upd = mysqli_prepare($conn, "UPDATE beneficiaries SET is_favorite=? WHERE beneficiary_id=? AND user_id=?");
            mysqli_stmt_bind_param($upd, 'iii', $new_val, $beneficiary_id, $user_id);
            mysqli_stmt_execute($upd);
            if (mysqli_stmt_affected_rows($upd) > 0) {
                $success = "Favorite status updated.";
            }
        }
    }
}

// Fetch beneficiaries
$search_q = $_GET['search'] ?? '';
$where = "user_id = ?";
$params = [$user_id];
$types = 'i';

if ($search_q !== '') {
    $where .= " AND (account_number LIKE ? OR beneficiary_name LIKE ? OR nickname LIKE ?)";
    $like = "%{$search_q}%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}

$stmt = mysqli_prepare($conn, "SELECT * FROM beneficiaries WHERE $where ORDER BY is_favorite DESC, created_at DESC");
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$beneficiaries = mysqli_stmt_get_result($stmt);

$active = 'beneficiaries';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficiaries - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/premium-theme.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .ben-card {
            background: rgba(18, 18, 18, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        .ben-card:hover {
            border-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }
        .ben-details h4 { margin: 0 0 5px 0; color: #fff; font-size: 16px; }
        .ben-details p { margin: 0; color: var(--text-muted); font-size: 13px; font-family: monospace; }
        .ben-actions { display: flex; gap: 10px; }
        .btn-icon { background: rgba(255,255,255,0.1); border: none; color: white; width: 35px; height: 35px; border-radius: 6px; cursor: pointer; transition: 0.2s; }
        .btn-icon:hover { background: rgba(255,255,255,0.2); }
        .btn-fav { color: #f1c40f; }
        .btn-danger { color: #e74c3c; }
        
        .form-shell { max-width: 600px; margin-bottom: 40px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <h1><i class="fa-solid fa-address-book" style="color: var(--accent-blue);"></i> Beneficiaries</h1>
                <p>Manage your saved accounts for quick and easy transfers.</p>
            </div>
        </header>

        <?php if (!empty($error)): ?>
            <div class="alert-flash danger"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-flash success"><i class="fa-solid fa-circle-check"></i> <?= $success ?></div>
        <?php endif; ?>

        <!-- Add Beneficiary Form -->
        <div class="form-shell">
            <div class="card" style="padding: 25px;">
                <h3 style="margin-top:0;">Add New Beneficiary</h3>
                
                <?php if ($step === 1): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <input type="hidden" name="action" value="verify_account">
                    <div class="form-group">
                        <label>Destination Account Number</label>
                        <input type="text" name="to_account" class="form-control" required placeholder="Enter 10-digit account number">
                    </div>
                    <button type="submit" class="btn-glow" style="width: 100%;"><i class="fa-solid fa-magnifying-glass"></i> Verify Account</button>
                </form>
                <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                    <input type="hidden" name="action" value="add_beneficiary">
                    <input type="hidden" name="verified_account" value="<?= htmlspecialchars($verified_account) ?>">
                    
                    <div class="form-group">
                        <label>Account Number</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($verified_account) ?>" readonly style="background: rgba(255,255,255,0.05); color: #888;">
                    </div>
                    <div class="form-group">
                        <label>Account Name (Verified)</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($verified_name) ?>" readonly style="background: rgba(255,255,255,0.05); color: #888;">
                    </div>
                    <div class="form-group">
                        <label>Nickname (Optional)</label>
                        <input type="text" name="nickname" class="form-control" placeholder="e.g. John's Rent">
                    </div>
                    <div class="form-group">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                            <input type="checkbox" name="is_favorite" value="1" style="width:18px; height:18px;">
                            Mark as Favorite
                        </label>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <a href="beneficiaries.php" class="btn" style="background: rgba(255,255,255,0.1); color: white; width: 40%; text-align:center; text-decoration:none; padding-top:12px;">Cancel</a>
                        <button type="submit" class="btn-glow" style="width: 60%;"><i class="fa-solid fa-plus"></i> Save Beneficiary</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Beneficiary List -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
            <h3>Saved Accounts</h3>
            <form method="GET" style="display:flex; gap:10px; width:300px;">
                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search_q) ?>">
                <button type="submit" class="btn" style="background: rgba(255,255,255,0.1); color:white; border:none; padding: 0 15px;"><i class="fa-solid fa-search"></i></button>
            </form>
        </div>

        <?php $count = 0; while($b = mysqli_fetch_assoc($beneficiaries)): $count++; ?>
            <div class="ben-card">
                <div class="ben-details">
                    <h4>
                        <?= htmlspecialchars($b['nickname'] ? $b['nickname'] : $b['beneficiary_name']) ?>
                        <?php if($b['is_favorite']): ?>
                            <i class="fa-solid fa-star" style="color: #f1c40f; font-size: 12px; margin-left: 5px;" title="Favorite"></i>
                        <?php endif; ?>
                    </h4>
                    <p><i class="fa-regular fa-credit-card"></i> <?= htmlspecialchars($b['account_number']) ?> &nbsp;&nbsp;|&nbsp;&nbsp; <i class="fa-regular fa-user"></i> <?= htmlspecialchars($b['beneficiary_name']) ?></p>
                </div>
                <div class="ben-actions">
                    <form method="POST" action="transfer.php" style="margin:0;">
                        <input type="hidden" name="preselect_account" value="<?= htmlspecialchars($b['account_number']) ?>">
                        <!-- Using GET in transfer to preselect is better, but POST works if we handle it. Actually, GET is standard. We will use a link instead. -->
                    </form>
                    <a href="transfer.php?to=<?= urlencode($b['account_number']) ?>" class="btn-icon" style="display:flex; align-items:center; justify-content:center; text-decoration:none; background: var(--accent-blue);" title="Transfer">
                        <i class="fa-solid fa-paper-plane"></i>
                    </a>

                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                        <input type="hidden" name="action" value="toggle_favorite">
                        <input type="hidden" name="beneficiary_id" value="<?= $b['beneficiary_id'] ?>">
                        <input type="hidden" name="new_status" value="<?= $b['is_favorite'] ? 0 : 1 ?>">
                        <button type="submit" class="btn-icon <?= $b['is_favorite'] ? 'btn-fav' : '' ?>" title="<?= $b['is_favorite'] ? 'Unfavorite' : 'Favorite' ?>">
                            <i class="<?= $b['is_favorite'] ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                        </button>
                    </form>

                    <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to remove this beneficiary?');">
                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                        <input type="hidden" name="action" value="delete_beneficiary">
                        <input type="hidden" name="beneficiary_id" value="<?= $b['beneficiary_id'] ?>">
                        <button type="submit" class="btn-icon btn-danger" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>

        <?php if($count === 0): ?>
            <div class="empty-state" style="text-align:center; padding: 40px; background: rgba(255,255,255,0.02); border-radius:12px; color: var(--text-muted);">
                <i class="fa-solid fa-address-book" style="font-size: 40px; opacity:0.5; margin-bottom:15px;"></i>
                <p>No beneficiaries found.</p>
            </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>
