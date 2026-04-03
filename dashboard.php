<?php
session_start();
include 'db_connect.php';




if (
    !isset($_SESSION['admin_id']) ||
    !in_array($_SESSION['admin_role'], ['sys_admin', 'manager', 'staff'])
) {
    echo "<script>
            alert('Access Denied: You do not have permission to view this content.');
            window.location.href='dashboard.php';
          </script>";
    exit();
}

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

<!-- =========================
     NAVBAR / HEADER
========================= -->
<?php include 'navbar.php'; ?>


</a>

</div>

</div>
</header>
<div class = "title_header">
<h2>Locker Inventory</h2>
<div class = "add-lock-btn">
<a href="add_locker.php">+ Add Locker</a>
</div>
</div>

<div class="locker-container">
<?php while ($row = mysqli_fetch_assoc($result)) { ?>
<div class="locker-card">    
    <h3>Locker L00<?php echo $row['locker_id']; ?></h3>

    <p><b>Location:</b> <?php echo $row['location']; ?></p>
    <p><b>Size:</b> <?php echo $row['size']; ?></p>

    <p>
  <b>Status:</b> 
  <span class="status <?php echo $row['status']; ?>">
    <?php echo $row['status']; ?>
  </span>
</p>

   <div class="locker-actions">
  <a href="edit_locker.php?id=<?php echo $row['locker_id']; ?>">Edit</a>
  <a href="delete_locker.php?id=<?php echo $row['locker_id']; ?>" onclick="return confirm('Delete this locker?')">Delete</a>
</div>

    <p><b>Price:</b> Php <?php echo $row['pricer_per_hr']; ?></p>

  </div>
<?php } ?>

</div>

</body>
</html>