<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Check if email exists
    $check_sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($result) > 0) {
        echo "Email already registered. <a href='register.php'>Try again</a>";
        exit();
    }

    // Insert into DB
    $insert_sql = "INSERT INTO users (full_name, email, phone_number, password, username)
                   VALUES ('$full_name', '$email', '$phone', '$password_hash', '$email')";

    if (mysqli_query($conn, $insert_sql)) {
      header("Location: login.php");
        //echo "Account created successfully! <a href='login.php'>Sign in</a>";
    } else {
        echo "Error: " . mysqli_error($conn);
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
  <h2>Create Account</h2>

  <form action="" method="POST">
    <input type="text" name="name" placeholder="Full Name">
    <input type="email" name="email" placeholder="Email">
    <input type="text" name="phone" placeholder="+63 9XX XXX XXXX">
    <input type="password" name="password" placeholder="Password">

    <button>Create Account</button>
  </form>

  <p style="text-align:center;">
    Already have an account? <a href="login.php">Sign In</a>
  </p>
</div>

</body>
</html>