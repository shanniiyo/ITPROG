<?php
/**
 * account.php
 * Lets the logged-in customer edit their name, email, and phone number.
 */
session_start();
include 'db_connect.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$msg_type = '';

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name    = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email        = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone_number = mysqli_real_escape_string($conn, trim($_POST['phone_number']));
    $new_password = trim($_POST['new_password']);
    $confirm_pass = trim($_POST['confirm_password']);

    // Check if email is taken by another user
    $check = "SELECT user_id FROM users WHERE email='$email' AND user_id != $user_id LIMIT 1";
    $check_result = mysqli_query($conn, $check);

    if (mysqli_num_rows($check_result) > 0) {
        $message  = "That email is already used by another account.";
        $msg_type = 'error';
    } elseif ($new_password != '' && $new_password !== $confirm_pass) {
        $message  = "New passwords do not match.";
        $msg_type = 'error';
    } else {
        // Build update query
        if ($new_password != '') {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users
                    SET full_name='$full_name', email='$email', phone_number='$phone_number',
                        password='$hashed', username='$email'
                    WHERE user_id=$user_id";
        } else {
            $sql = "UPDATE users
                    SET full_name='$full_name', email='$email', phone_number='$phone_number',
                        username='$email'
                    WHERE user_id=$user_id";
        }

        if (mysqli_query($conn, $sql)) {
            // Refresh session name
            $_SESSION['full_name'] = $full_name;
            $message  = "Profile updated successfully!";
            $msg_type = 'success';
        } else {
            $message  = "Error: " . mysqli_error($conn);
            $msg_type = 'error';
        }
    }
}

// --- Fetch current user data ---
$sql    = "SELECT * FROM users WHERE user_id=$user_id LIMIT 1";
$result = mysqli_query($conn, $sql);
$user   = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Account | SmartLocker</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .page { background: #f6f7fb; padding: 30px 0 60px; min-height: calc(100vh - 68px); }
    .account-wrapper { max-width: 560px; margin: 0 auto; }
    .account-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 32px; box-shadow: 0 6px 22px rgba(15,23,42,0.06); }
    .account-card h2 { margin: 0 0 4px; font-size: 22px; font-weight: 800; color: #0f172a; }
    .account-card p.sub { margin: 0 0 24px; color: #64748b; font-size: 14px; }
    .form__label { display: block; margin: 16px 0 6px; font-size: 12px; font-weight: 700; color: #0f172a; }
    .form__control { width: 100%; padding: 11px 12px; border-radius: 10px; border: 1px solid #e5e7eb; background: #f8fafc; font-size: 14px; box-sizing: border-box; outline: none; }
    .form__control:focus { border-color: rgba(59,130,246,0.65); box-shadow: 0 0 0 4px rgba(59,130,246,0.1); background: #fff; }
    .divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
    .section-label { font-size: 13px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 4px; }
    .btn-save { width: 100%; padding: 12px; border-radius: 10px; background: linear-gradient(90deg,#0b58ff,#0ea5e9); color: #fff; font-weight: 700; font-size: 14px; border: none; cursor: pointer; margin-top: 24px; }
    .btn-save:hover { opacity: 0.9; }
    .alert { padding: 12px 14px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; }
    .alert--success { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; }
    .alert--error { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }
    .hint { font-size: 12px; color: #94a3b8; margin-top: 4px; }
  </style>
</head>
<body>

<?php include 'navbar_client.php'; ?>

<div class="page">
  <div class="container account-wrapper">
    <div class="account-card">
      <h2>My Account</h2>
      <p class="sub">Update your personal information below.</p>

      <?php if ($message): ?>
        <div class="alert alert--<?php echo $msg_type; ?>">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">

        <!-- Profile Info -->
        <p class="section-label">Profile Information</p>

        <label class="form__label">Full Name</label>
        <input class="form__control" type="text" name="full_name"
               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>

        <label class="form__label">Email Address</label>
        <input class="form__control" type="email" name="email"
               value="<?php echo htmlspecialchars($user['email']); ?>" required>

        <label class="form__label">Phone Number</label>
        <input class="form__control" type="text" name="phone_number"
               value="<?php echo htmlspecialchars($user['phone_number']); ?>"
               placeholder="+63 9XX XXX XXXX">

        <hr class="divider">

        <!-- Change Password -->
        <p class="section-label">Change Password</p>
        <p class="hint">Leave blank if you don't want to change your password.</p>

        <label class="form__label">New Password</label>
        <input class="form__control" type="password" name="new_password" placeholder="New password">

        <label class="form__label">Confirm New Password</label>
        <input class="form__control" type="password" name="confirm_password" placeholder="Repeat new password">

        <button class="btn-save" type="submit">Save Changes</button>
      </form>
    </div>
  </div>
</div>

</body>
</html>