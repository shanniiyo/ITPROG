<?php
session_start();
include 'db_connect.php';

if (
    !isset($_SESSION['admin_id']) ||
    !in_array($_SESSION['admin_role'], ['sys_admin', 'manager'])
) {
    echo "<script>
            alert('Access Denied: You do not have permission to view this content.');
            window.location.href='dashboard.php';
          </script>";
    exit();
}

else;

$id = $_GET['id'];

$sql = "SELECT * FROM locker_rsvp WHERE locker_id='$id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Locker</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include 'navbar.php'; ?>

<div class="edit-locker">
  <h2>Edit Locker</h2>

  <form action="update_locker.php" method="POST">
    <input type="hidden" name="id" value="<?php echo $row['locker_id']; ?>">

    <label>Location</label>
    <input type="text" name="location" value="<?php echo $row['location']; ?>">

    <label>Size</label>
    <select name="size">
      <option value="small" <?php if($row['size']=="small") echo "selected"; ?>>Small</option>
      <option value="medium" <?php if($row['size']=="medium") echo "selected"; ?>>Medium</option>
      <option value="large" <?php if($row['size']=="large") echo "selected"; ?>>Large</option>
    </select>

    <label>Price per Hour</label>
    <input type="number" step="10.00" name="pricer_per_hr"
    value="<?php echo $row['pricer_per_hr']; ?>">

    <label>Status</label>
    <select name="status">
      <option value="available" <?php if($row['status']=="available") echo "selected"; ?>>Available</option>
      <option value="occupied" <?php if($row['status']=="occupied") echo "selected"; ?>>Occupied</option>
      <option value="out_of_service" <?php if($row['status']=="out_of_service") echo "selected"; ?>>Out of Service</option>
    </select>

    <button>Update</button>
  </form>
</div>

</body>
</html>