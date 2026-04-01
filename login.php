<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email='$email' OR phone_number='$email' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            // Successful login
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];

            header("Location: index.php");
            exit();
        } else {
            echo "Incorrect password. <a href='login.php'>Try again</a>";
        }
    } else {
        echo "No account found with that email/phone. <a href='register.php'>Register</a>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style(itprog).css">
</head>
<body>

<div class="card">
  <h2>Welcome Back</h2>
  <p>Sign in to manage your luggage storage</p>

  <form action="" method="POST">
    <input type="text" name="email" placeholder="Email or Phone">
    <input type="password" name="password" placeholder="Password">

    <button>Sign In</button>
  </form>

  <p style="text-align:center;">
    Don't have an account? <a href="register.php">Create Account</a>
  </p>
</div>

</body>
</html>