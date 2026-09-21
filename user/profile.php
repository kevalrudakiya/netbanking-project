<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
requireUserLogin();

$user_id = $_SESSION['user_id'];
$account = validateAccountStatus($conn, $user_id, 'view');

// Fetch complete user data
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE user_id=?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));



$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $full_name = sanitize($_POST['full_name']);
            $email     = sanitize($_POST['email']);
            $phone     = sanitize($_POST['phone']);
            $address   = sanitize($_POST['address']);
            $dob       = sanitize($_POST['dob']);

            if (empty($full_name) || empty($email) || empty($phone)) {
                $error = "Name, Email, and Phone are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email format.";
            } else {
                // Check for duplicates
                $chk = mysqli_prepare($conn, "SELECT user_id FROM users WHERE (email=? OR phone=?) AND user_id!=?");
                mysqli_stmt_bind_param($chk, 'ssi', $email, $phone, $user_id);
                mysqli_stmt_execute($chk);
                if (mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0) {
                    $error = "Email or Phone is already in use by another account.";
                } else {
                    $profile_photo = $user['profile_photo'];
                    
                    // Handle file upload
                    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                        $filename = $_FILES['profile_photo']['name'];
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        
                        if (in_array($ext, $allowed) && $_FILES['profile_photo']['size'] < 2000000) { // 2MB limit
                            $new_filename = uniqid('profile_') . '.' . $ext;
                            $dest = '../assets/uploads/' . $new_filename;
                            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dest)) {
                                $profile_photo = $new_filename;
                                // Optionally delete old photo if not default.png
                                if ($user['profile_photo'] !== 'default.png' && file_exists('../assets/uploads/' . $user['profile_photo'])) {
                                    unlink('../assets/uploads/' . $user['profile_photo']);
                                }
                            } else {
                                $error = "Failed to upload photo.";
                            }
                        } else {
                            $error = "Invalid photo format or file too large (Max 2MB).";
                        }
                    }

                    if (!$error) {
                        $upd = mysqli_prepare($conn, "UPDATE users SET full_name=?, email=?, phone=?, address=?, dob=?, profile_photo=? WHERE user_id=?");
                        mysqli_stmt_bind_param($upd, 'ssssssi', $full_name, $email, $phone, $address, $dob, $profile_photo, $user_id);
                        if (mysqli_stmt_execute($upd)) {
                            $success = "Profile updated successfully!";
                            logAudit($conn, 'user', $user_id, 'user_profile_update', 'User updated their personal details');
                            // Refresh local user data
                            $user['full_name'] = $full_name;
                            $user['email'] = $email;
                            $user['phone'] = $phone;
                            $user['address'] = $address;
                            $user['dob'] = $dob;
                            $user['profile_photo'] = $profile_photo;
                        } else {
                            $error = "Failed to update profile.";
                        }
                    }
                }
            }
        } elseif ($action === 'change_password') {
            $current_pw = $_POST['current_password'];
            $new_pw     = $_POST['new_password'];
            $conf_pw    = $_POST['confirm_password'];

            if (!verifyPassword($current_pw, $user['password'])) {
                $error = "Current password is incorrect.";
            } elseif ($new_pw !== $conf_pw) {
                $error = "New passwords do not match.";
            } elseif (strlen($new_pw) < 6) {
                $error = "New password must be at least 6 characters.";
            } else {
                $hashed = hashPassword($new_pw);
                $upd = mysqli_prepare($conn, "UPDATE users SET password=? WHERE user_id=?");
                mysqli_stmt_bind_param($upd, 'si', $hashed, $user_id);
                if (mysqli_stmt_execute($upd)) {
                    $success = "Password changed successfully!";
                    logAudit($conn, 'user', $user_id, 'user_password_change', 'User changed their password');
                    sendNotification($conn, $user_id, "Security Alert", "Your account password was recently changed.");
                    $user['password'] = $hashed; // update local hash for immediate state consistency
                } else {
                    $error = "Failed to update password.";
                }
            }
        } elseif ($action === 'change_pin') {
            if (!$account) {
                $error = "No active account found to change PIN.";
            } else {
                // Must ensure account is not closed for this
                validateAccountStatus($conn, $user_id, 'settings');

                $current_pin = $_POST['current_pin'];
                $new_pin     = $_POST['new_pin'];
                $conf_pin    = $_POST['confirm_pin'];

                if (!verifyPassword($current_pin, $account['pin'])) {
                    $error = "Current PIN is incorrect.";
                } elseif (!preg_match('/^[0-9]{4}$/', $new_pin)) {
                    $error = "New PIN must be exactly 4 digits.";
                } elseif ($new_pin !== $conf_pin) {
                    $error = "New PINs do not match.";
                } elseif ($current_pin === $new_pin) {
                    $error = "New PIN cannot be the same as the old PIN.";
                } else {
                    $hashed = hashPassword($new_pin);
                    $upd = mysqli_prepare($conn, "UPDATE accounts SET pin=? WHERE account_id=?");
                    mysqli_stmt_bind_param($upd, 'si', $hashed, $account['account_id']);
                    if (mysqli_stmt_execute($upd)) {
                        $success = "Transaction PIN changed successfully!";
                        logAudit($conn, 'user', $user_id, 'user_pin_change', 'User changed their transaction PIN', $account['account_id']);
                        sendNotification($conn, $user_id, "Security Alert", "Your transaction PIN was recently changed.");
                        $account['pin'] = $hashed;
                    } else {
                        $error = "Failed to update PIN.";
                    }
                }
            }
        }
    }
}

$active = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="../assets/css/premium-theme.css">
    <link rel="stylesheet" href="../assets/css/app-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-grid { display: grid; grid-template-columns: 300px 1fr; gap: 30px; align-items: start; }
        .card { background: var(--surface-light); padding: 25px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.05); margin-bottom: 30px; }
        .card h3 { margin-top: 0; color: var(--accent-blue); margin-bottom: 20px; font-size: 18px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; }
        .profile-sidebar { text-align: center; }
        .profile-photo { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--accent-blue); margin: 0 auto 15px auto; display: block; background: rgba(0,0,0,0.2); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group.full { grid-column: span 2; }
        
        .account-badge { background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px; margin-top: 20px; text-align: left; }
        .account-badge .label { color: var(--text-muted); font-size: 13px; display: block; margin-bottom: 5px; }
        .account-badge .val { color: white; font-weight: 600; font-size: 15px; }
        
        @media (max-width: 768px) {
            .profile-grid { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .form-group.full { grid-column: span 1; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <h1><i class="fa-solid fa-user-circle" style="color: var(--accent-blue);"></i> My Profile</h1>
                <p>Manage your personal details and security settings.</p>
            </div>
        </header>

        <?php if ($error): ?>
            <div class="alert-flash error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert-flash success"><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="profile-grid">
            <!-- Sidebar -->
            <div class="card profile-sidebar">
                <?php $photoPath = ($user['profile_photo'] === 'default.png') ? '../assets/images/avatar.png' : '../assets/uploads/' . htmlspecialchars($user['profile_photo']); ?>
                <img src="<?= $photoPath ?>" alt="Profile Photo" class="profile-photo" onerror="this.src='../assets/images/avatar.png';">
                <h3 style="border:none; padding:0; margin-bottom: 5px; color: white; font-size: 20px;"><?= htmlspecialchars($user['full_name']) ?></h3>
                <p style="color: var(--text-muted); font-size: 14px; margin-top:0;">@<?= htmlspecialchars($user['username']) ?></p>

                <?php if($account): ?>
                <div class="account-badge">
                    <div style="margin-bottom: 12px;">
                        <span class="label">Account Number</span>
                        <span class="val"><?= htmlspecialchars($account['account_number']) ?></span>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <span class="label">Balance</span>
                        <span class="val"><?= formatCurrency($account['balance']) ?></span>
                    </div>
                    <div>
                        <span class="label">Status</span>
                        <span class="badge-status badge-<?= $account['status'] ?>"><?= ucfirst($account['status']) ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Main Content -->
            <div>
                <!-- Personal Details -->
                <div class="card">
                    <h3>Personal Details</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($user['dob'] ?? '') ?>">
                            </div>
                            <div class="form-group full">
                                <label>Residential Address</label>
                                <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group full">
                                <label>Update Profile Photo (Optional)</label>
                                <input type="file" name="profile_photo" class="form-control" accept="image/png, image/jpeg, image/jpg, image/gif" style="padding: 10px;">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top: 20px; width: 100%;"><i class="fa-solid fa-save"></i> Save Profile</button>
                    </form>
                </div>

                <!-- Security Details Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Change Password -->
                    <div class="card">
                        <h3>Change Password</h3>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" class="form-control" minlength="6" required>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                            </div>
                            <button type="submit" class="btn" style="width: 100%; background: rgba(255,255,255,0.1); color: white;"><i class="fa-solid fa-lock"></i> Update Password</button>
                        </form>
                    </div>

                    <!-- Change PIN -->
                    <?php if($account): ?>
                    <div class="card">
                        <h3>Change Transaction PIN</h3>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                            <input type="hidden" name="action" value="change_pin">
                            
                            <div class="form-group">
                                <label>Current PIN</label>
                                <input type="password" name="current_pin" class="form-control" pattern="[0-9]{4}" required>
                            </div>
                            <div class="form-group">
                                <label>New PIN (4 Digits)</label>
                                <input type="password" name="new_pin" class="form-control" pattern="[0-9]{4}" required>
                            </div>
                            <div class="form-group">
                                <label>Confirm New PIN</label>
                                <input type="password" name="confirm_pin" class="form-control" pattern="[0-9]{4}" required>
                            </div>
                            <button type="submit" class="btn" style="width: 100%; background: rgba(255,255,255,0.1); color: white;"><i class="fa-solid fa-key"></i> Update PIN</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </main>
</div>

</body>
</html>
