<?php
session_start();
include 'db_connect.php';

// FIX: was redirecting to dashboard.php (itself) — now redirects to admin_login.php
if (
    !isset($_SESSION['admin_id']) ||
    !in_array($_SESSION['admin_role'], ['sys_admin', 'manager', 'staff'])
) {
    echo "<script>
            alert('Access Denied: You do not have permission to view this content.');
            window.location.href='admin_login.php';
          </script>";
    exit();
}

$role = $_SESSION['admin_role'];

// Fetch lockers
$sql = "SELECT * FROM locker_rsvp ORDER BY locker_id ASC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style.css">
<title>Locker Inventory | SmartLocker</title>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="title_header">
  <h2>Locker Inventory</h2>
  <?php if ($role == 'sys_admin'): ?>
  <div class="add-lock-btn">
    <a href="add_locker.php">+ Add Locker</a>
  </div>
  <?php endif; ?>
</div>

<div class="locker-container">
<?php while ($row = mysqli_fetch_assoc($result)): ?>
<div class="locker-card">
  <h3>Locker #<?php echo $row['locker_id']; ?></h3>
  <p><b>Location:</b> <?php echo htmlspecialchars($row['location']); ?></p>
  <p><b>Size:</b> <?php echo ucfirst($row['size']); ?></p>
  <p>
    <b>Status:</b>
    <span class="status <?php echo $row['status']; ?>">
      <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
    </span>
  </p>
  <p><b>Price:</b> ₱<?php echo number_format($row['pricer_per_hr'], 2); ?>/hr</p>
  <p><b>Active:</b> <?php echo $row['is_active'] ? 'Yes' : 'No'; ?></p>

  <div class="locker-actions">
    <!-- sys_admin and staff can edit (staff restricted inside edit_locker.php) -->
    <?php if (in_array($role, ['sys_admin', 'staff'])): ?>
      <a href="edit_locker.php?id=<?php echo $row['locker_id']; ?>">Edit</a>
    <?php endif; ?>

    <!-- Only sys_admin can delete -->
    <?php if ($role == 'sys_admin'): ?>
      <a href="delete_locker.php?id=<?php echo $row['locker_id']; ?>"
         onclick="return confirm('Delete Locker #<?php echo $row['locker_id']; ?>? This cannot be undone.')">
        Delete
      </a>
    <?php endif; ?>
  </div>
</div>
<?php endwhile; ?>
</div>

</body>
</html>