<?php
require_once '../config/session.php';
require_once '../includes/functions.php';
redirectIfUserLoggedIn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please try again.";
    } else {
        $full_name = sanitize($_POST['full_name']);
    $email     = sanitize($_POST['email']);
    $phone     = sanitize($_POST['phone']);
    $username  = sanitize($_POST['username']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];
    $pin       = $_POST['pin'];

    // Validation
    if (empty($full_name) || empty($email) || empty($phone) || empty($username) || empty($password) || empty($pin)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Phone number must be 10 digits.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (!preg_match('/^[0-9]{4}$/', $pin)) {
        $error = "PIN must be exactly 4 digits.";
    } else {
        // Check if username/email/phone already exists
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE username=?");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "Username already registered.";
        } else {
            $hashed_password = hashPassword($password);
            $hashed_pin      = hashPassword($pin);
            $account_number  = generateAccountNumber();

            // Insert user
            $stmt2 = mysqli_prepare($conn, "INSERT INTO users (full_name, email, phone, username, password) VALUES (?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt2, 'sssss', $full_name, $email, $phone, $username, $hashed_password);

            if (mysqli_stmt_execute($stmt2)) {
                $user_id = mysqli_insert_id($conn);

                // Create account for user
                $stmt3 = mysqli_prepare($conn, "INSERT INTO accounts (user_id, account_number, pin) VALUES (?,?,?)");
                mysqli_stmt_bind_param($stmt3, 'iss', $user_id, $account_number, $hashed_pin);
                mysqli_stmt_execute($stmt3);

                $success = "Registration successful! Your Account Number is: <strong>$account_number</strong>. <a href='login.php'>Login here</a>";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1a237e, #0d47a1); min-height: 100vh; display: flex; align-items: center; }
        .card { border: none; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .card-header { background: linear-gradient(135deg, #1a237e, #1565c0); border-radius: 16px 16px 0 0 !important; }
        .btn-primary { background: linear-gradient(135deg, #1a237e, #1565c0); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #0d47a1, #1a237e); }
        .form-control:focus { border-color: #1565c0; box-shadow: 0 0 0 0.2rem rgba(21,101,192,0.25); }
        .login-link { color: #1565c0; text-decoration: none; font-weight: 600; transition: all 0.3s ease; }
        .login-link:hover { color: #0d47a1; text-decoration: underline; }
        .text-muted-custom { color: #5a6268; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header text-white text-center py-4">
                    <h3><i class="fas fa-university me-2"></i><?= APP_NAME ?></h3>
                    <p class="mb-0">Create Your Bank Account</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= $success ?></div>
                    <?php endif; ?>

                    <form method="POST" id="registerForm">
                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                        <!-- Personal Info -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Full Name</label>
                                <input type="text" name="full_name" class="form-control" placeholder="John Doe" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="johndoe123" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="john@email.com" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="text" name="phone" class="form-control" placeholder="10 digit number" maxlength="10" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required autocomplete="new-password">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required autocomplete="new-password">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">4-Digit Transaction PIN</label>
                                <input type="password" name="pin" class="form-control" placeholder="Enter 4 digit PIN" maxlength="4" required autocomplete="new-password">
                                <small class="text-muted">This PIN will be used for transactions</small>
                            </div>
                        </div>
                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </div>
                    </form>
                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="text-muted-custom mb-0">Already have an account? <a href="login.php" class="login-link">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const phone = document.querySelector('[name=phone]').value;
    const pin   = document.querySelector('[name=pin]').value;
    const pass  = document.querySelector('[name=password]').value;
    const conf  = document.querySelector('[name=confirm_password]').value;
    if (!/^\d{10}$/.test(phone)) { alert('Phone must be 10 digits!'); e.preventDefault(); return; }
    if (!/^\d{4}$/.test(pin))    { alert('PIN must be 4 digits!'); e.preventDefault(); return; }
    if (pass !== conf)            { alert('Passwords do not match!'); e.preventDefault(); return; }
});
</script>
</body>
</html>
