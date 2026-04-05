<?php
/**
 * forgot_password.php
 * Simulated password recovery.
 * Since email is "dummy", we just show the new password on screen
 * instead of actually sending an email.
 */
include 'db_connect.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));

    // Check if email exists
    $sql = "SELECT * FROM users WHERE email='$email' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // Generate a simple temporary password
        $temp_password = 'Reset' . rand(1000, 9999);
        $hashed = password_hash($temp_password, PASSWORD_DEFAULT);

        // Update the password in the database
        $update = "UPDATE users SET password='$hashed' WHERE email='$email'";
        mysqli_query($conn, $update);

        $success = true;
        // In a real system, this would be emailed.
        // Since email is simulated, we display it directly.
        $message = "Password reset! Your temporary password is: <strong>$temp_password</strong><br>Please log in and change it right away.";
    } else {
        $message = "No account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forgot Password | SmartLocker</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { background: #f6f7fb; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .auth-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 36px 32px; width: 100%; max-width: 420px; box-shadow: 0 8px 28px rgba(15,23,42,0.07); }
    .auth-card h2 { margin: 0 0 6px; font-size: 24px; font-weight: 800; color: #0f172a; }
    .auth-card p.sub { margin: 0 0 24px; color: #64748b; font-size: 14px; }
    .form__label { display: block; margin: 0 0 6px; font-size: 12px; font-weight: 700; color: #0f172a; }
    .form__control { width: 100%; padding: 11px 12px; border-radius: 10px; border: 1px solid #e5e7eb; background: #f8fafc; font-size: 14px; box-sizing: border-box; outline: none; margin-bottom: 16px; }
    .form__control:focus { border-color: rgba(59,130,246,0.65); box-shadow: 0 0 0 4px rgba(59,130,246,0.1); background: #fff; }
    .btn-submit { width: 100%; padding: 12px; border-radius: 10px; background: linear-gradient(90deg,#0b58ff,#0ea5e9); color: #fff; font-weight: 700; font-size: 14px; border: none; cursor: pointer; }
    .btn-submit:hover { opacity: 0.9; }
    .alert { padding: 12px 14px; border-radius: 10px; font-size: 14px; margin-bottom: 16px; }
    .alert--success { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; }
    .alert--error { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }
    .back-link { display: block; text-align: center; margin-top: 16px; font-size: 13px; color: #3b82f6; text-decoration: none; }
    .back-link:hover { text-decoration: underline; }
  </style>
</head>
<body>
<div class="auth-card">
  <h2>Forgot Password</h2>
  <p class="sub">Enter your registered email and we'll reset your password.</p>

  <?php if ($message): ?>
    <div class="alert <?php echo $success ? 'alert--success' : 'alert--error'; ?>">
      <?php echo $message; ?>
    </div>
  <?php endif; ?>

  <?php if (!$success): ?>
  <form method="POST" action="">
    <label class="form__label">Email Address</label>
    <input class="form__control" type="email" name="email" placeholder="yourname@email.com" required>
    <button class="btn-submit" type="submit">Reset Password</button>
  </form>
  <?php endif; ?>

  <a class="back-link" href="login.php">← Back to Sign In</a>
</div>
</body>
</html>