<?php
session_start();
include 'db_connect.php';

// If already logged in, go to home
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];

    $sql    = "SELECT * FROM users WHERE email='$email' OR phone_number='$email' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "No account found with that email or phone number.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign In | SmartLocker</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { background: #f6f7fb; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
    .auth-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 36px 32px; width: 100%; max-width: 420px; box-shadow: 0 8px 28px rgba(15,23,42,0.07); }
    .auth-card h2 { margin: 0 0 4px; font-size: 24px; font-weight: 800; color: #0f172a; }
    .auth-card p.sub { margin: 0 0 24px; color: #64748b; font-size: 14px; }
    .form__label { display: block; margin: 0 0 6px; font-size: 12px; font-weight: 700; color: #0f172a; }
    .form__control { width: 100%; padding: 11px 12px; border-radius: 10px; border: 1px solid #e5e7eb; background: #f8fafc; font-size: 14px; box-sizing: border-box; outline: none; margin-bottom: 14px; }
    .form__control:focus { border-color: rgba(59,130,246,0.65); box-shadow: 0 0 0 4px rgba(59,130,246,0.1); background: #fff; }
    .forgot-row { text-align: right; margin-top: -8px; margin-bottom: 16px; }
    .forgot-link { font-size: 13px; color: #3b82f6; text-decoration: none; }
    .forgot-link:hover { text-decoration: underline; }
    .btn-submit { width: 100%; padding: 12px; border-radius: 10px; background: linear-gradient(90deg,#0b58ff,#0ea5e9); color: #fff; font-weight: 700; font-size: 14px; border: none; cursor: pointer; }
    .btn-submit:hover { opacity: 0.9; }
    .alert--error { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 14px; border-radius: 10px; font-size: 14px; margin-bottom: 16px; }
    .bottom-link { text-align: center; margin-top: 18px; font-size: 14px; color: #64748b; }
    .bottom-link a { color: #3b82f6; text-decoration: none; font-weight: 600; }
    .bottom-link a:hover { text-decoration: underline; }
    .brand-top { text-align: center; margin-bottom: 20px; font-size: 20px; font-weight: 800; color: #0b58ff; }
  </style>
</head>
<body>
<div class="auth-card">
  <div class="brand-top">🔒 SmartLocker</div>
  <h2>Welcome Back</h2>
  <p class="sub">Sign in to manage your luggage storage.</p>

  <?php if ($error): ?>
    <div class="alert--error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <label class="form__label">Email or Phone Number</label>
    <input class="form__control" type="text" name="email" placeholder="yourname@email.com" required>

    <label class="form__label">Password</label>
    <input class="form__control" type="password" name="password" placeholder="Your password" required>

    <div class="forgot-row">
      <a class="forgot-link" href="forgot_password.php">Forgot password?</a>
    </div>

    <button class="btn-submit" type="submit">Sign In</button>
  </form>

  <div class="bottom-link">
    Don't have an account? <a href="register.php">Create Account</a>
  </div>
</div>
</body>
</html>