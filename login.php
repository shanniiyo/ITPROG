<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="card">
  <h2>Welcome Back</h2>
  <p>Sign in to manage your luggage storage</p>

  <form action="login_process.php" method="POST">
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