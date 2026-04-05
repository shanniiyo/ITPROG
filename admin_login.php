<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM admin_user WHERE username='$username' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);

        if (password_verify($password, $admin['password'])) {

            // Store session
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_role'] = $admin['role'];

            header("Location: dashboard.php");
            exit();

        } else {
            echo "Incorrect password.";
        }

    } else {
        echo "Admin account not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style(itprog).css">
<title>Admin Login</title>
</head>
<body>

<div class="card">
  <h2>Admin Login</h2>
  <p>Authorized personnel only</p>

  <form method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>

    <button>Login</button>
  </form>

  <p style="text-align:center;">
    <a href="login.php">Back to User Login</a>
  </p>
</div>

</body>
</html>