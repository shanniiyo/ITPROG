<?php
session_start();
include 'db_connect.php';

if (
    !isset($_SESSION['admin_id']) ||
    $_SESSION['admin_role'] != 'sys_admin'
) {
    echo "<script>
            alert('Access Denied: You do not have permission to view this content.');
            window.location.href='dashboard.php';
          </script>";
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Add Locker</title>
<link rel="stylesheet" href="style.css">

</head>
<body>

<!-- =========================
     NAVBAR / HEADER
========================= -->
<?php include 'navbar.php'; ?>

<div class = "hero">
<div class = "Add_Locker">
<h2>Add Locker</h2>

<form action="insert_locker.php" method="POST">
  <input type="text" name="location" placeholder="Location" required>
  <input type="number" step="10.00" name="price" placeholder="Price per hour">
  <select name="size">
    <option value="small">Small</option>
    <option value="medium">Medium</option>
    <option value="large">Large</option>
  </select>

  <select name="status">
    <option value="available">Available</option>
    <option value="occupied">Occupied</option>
    <option value="out_of_service">Out of Service</option>
  </select>

  <button>Add Locker</button>
</form>
</div>
</div>
</body>
</html>