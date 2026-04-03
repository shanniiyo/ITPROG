<?php
session_start();
include 'db_connect.php';

// Fetch lockers
$sql = "SELECT * FROM locker_rsvp";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style.css">
<title>Locker Inventory</title>
</head>
<body>

<h2>Locker Inventory</h2>
<a href="add_locker.php">+ Add Locker</a>

<div style="display:flex; flex-wrap:wrap; gap:30px;">

<?php while ($row = mysqli_fetch_assoc($result)) { ?>
  <div style="border:1px solid #ccc; padding:15px; width:250px; border-radius:10px;">
    
    <h3>Locker L00<?php echo $row['locker_id']; ?></h3>

    <p><b>Location:</b> <?php echo $row['location']; ?></p>
    <p><b>Size:</b> <?php echo $row['size']; ?></p>

    <p>
      <b>Status:</b> 
      <?php echo $row['status']; ?>
    </p>

    <a href="edit_locker.php?id=<?php echo $row['locker_id']; ?>">Edit</a> |
    <a href="delete_locker.php?id=<?php echo $row['locker_id']; ?>" onclick="return confirm('Delete this locker?')">Delete</a>

    <p><b>Price:</b> Php <?php echo $row['pricer_per_hr']; ?></p>

  </div>
<?php } ?>

</div>

</body>
</html>