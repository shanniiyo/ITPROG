<?php
include 'db_connect.php';

$id = $_GET['id'];

$sql = "SELECT * FROM locker_rsvp WHERE locker_id='$id'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
?>

<h2>Edit Locker</h2>

<form action="update_locker.php" method="POST">
  <input type="hidden" name="id" value="<?php echo $row['locker_id']; ?>">

  <input type="text" name="location" value="<?php echo $row['location']; ?>">

  <select name="size">
    <option value="small" <?php if($row['size']=="small") echo "selected"; ?>>Small</option>
    <option value="medium" <?php if($row['size']=="medium") echo "selected"; ?>>Medium</option>
    <option value="large" <?php if($row['size']=="large") echo "selected"; ?>>Large</option>
  </select>

    <input type="number" step="10.00" name="pricer_per_hr" placeholder="Price per hour">

  <select name="status">
    <option value="available" <?php if($row['status']=="available") echo "selected"; ?>>Available</option>
    <option value="occupied" <?php if($row['status']=="occupied") echo "selected"; ?>>Occupied</option>
    <option value="out_of_service" <?php if($row['status']=="out_of_service") echo "selected"; ?>>Out of Service</option>
  </select>

  <button>Update</button>
</form>