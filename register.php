<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="card">
  <h2>Create Account</h2>

  <form action="register_process.php" method="POST">
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