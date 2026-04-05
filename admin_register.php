<?php
session_start();
include 'db_connect.php';

/* =========================
   PROTECT PAGE (ONLY SYS_ADMIN)
*/
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] != 'sys_admin') {

    echo "<script>
            alert('Access Denied: You do not have permission to view this content.');
            window.location.href='dashboard.php';
          </script>";
    exit();
}



if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $full_name = mysqli_real_escape_string($conn, $_POST['name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Check duplicate username
    $check = "SELECT * FROM admin_user WHERE username='$username'";
    $result = mysqli_query($conn, $check);

    if (mysqli_num_rows($result) > 0) {
        echo "Username already exists.";
        exit();
    }

    $sql = "INSERT INTO admin_user (username, password, full_name, role)
            VALUES ('$username', '$password_hash', '$full_name', '$role')";

    if (mysqli_query($conn, $sql)) {
        echo "Admin created successfully. <a href='dashboard.php'>Back to Dashboard</a>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style(itprog).css">
<title>Create Admin</title>
</head>
<body>

<header><?php include 'navbar.php'; ?></header>

<div class="card">
  <h2>Create Admin Account</h2>

  <form method="POST">
    <input type="text" name="name" placeholder="Full Name" required>
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>

    <select name="role" required>
      <option value="staff">Staff</option>
      <option value="manager">Manager</option>
      <option value="sys_admin">System Admin</option>
    </select>

    <button>Create Admin</button>
  </form>

  <p style="text-align:center;">
    <a href="dashboard.php">Back to Dashboard</a>
  </p>
</div>

</body>
</html>