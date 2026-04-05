<?php
session_start();
include 'db_connect.php';

if (
    !isset($_SESSION['admin_id']) ||
    !in_array($_SESSION['admin_role'], ['sys_admin', 'staff'])
) {
    echo "<script>
            alert('Access Denied: You do not have permission to view this content.');
            window.location.href='dashboard.php';
          </script>";
    exit();
}

$role = $_SESSION['admin_role'];

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}
$id = (int) $_GET['id'];

$sql    = "SELECT * FROM locker_rsvp WHERE locker_id=$id LIMIT 1";
$result = mysqli_query($conn, $sql);
$row    = mysqli_fetch_assoc($result);

if (!$row) {
    echo "<script>alert('Locker not found.'); window.location.href='dashboard.php';</script>";
    exit();
}

// Staff can only set status to 'out_of_service' (Maintenance/Disabled per spec)
// sys_admin can change everything
$staff_statuses = ['out_of_service'];  // what staff is allowed to set
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Locker | SmartLocker</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="edit-locker">
  <h2>Edit Locker #<?php echo $row['locker_id']; ?></h2>

  <?php if ($role == 'staff'): ?>
    <p style="font-size:13px; color:#64748b; text-align:center; margin-bottom:16px;">
      As staff, you can only update the locker status.
    </p>
  <?php endif; ?>

  <form action="update_locker.php" method="POST">
    <input type="hidden" name="id" value="<?php echo $row['locker_id']; ?>">

    <?php if ($role == 'sys_admin'): ?>
      <!-- sys_admin: full edit -->
      <label>Location</label>
      <input type="text" name="location" value="<?php echo htmlspecialchars($row['location']); ?>" required>

      <label>Size</label>
      <select name="size">
        <option value="small"  <?php echo $row['size']=='small'  ? 'selected' : ''; ?>>Small</option>
        <option value="medium" <?php echo $row['size']=='medium' ? 'selected' : ''; ?>>Medium</option>
        <option value="large"  <?php echo $row['size']=='large'  ? 'selected' : ''; ?>>Large</option>
      </select>

      <label>Price per Hour (₱)</label>
      <input type="number" step="10.00" name="pricer_per_hr" value="<?php echo $row['pricer_per_hr']; ?>" required>

      <label>Status</label>
      <select name="status">
        <option value="available"    <?php echo $row['status']=='available'    ? 'selected' : ''; ?>>Available</option>
        <option value="occupied"     <?php echo $row['status']=='occupied'     ? 'selected' : ''; ?>>Occupied</option>
        <option value="out_of_service" <?php echo $row['status']=='out_of_service' ? 'selected' : ''; ?>>Out of Service</option>
      </select>

      <label>Active</label>
      <select name="is_active">
        <option value="1" <?php echo $row['is_active'] ? 'selected' : ''; ?>>Yes</option>
        <option value="0" <?php echo !$row['is_active'] ? 'selected' : ''; ?>>No (Disabled)</option>
      </select>

    <?php else: ?>
      <!-- staff: status only, pass other fields as hidden so update_locker.php can keep them -->
      <input type="hidden" name="location"     value="<?php echo htmlspecialchars($row['location']); ?>">
      <input type="hidden" name="size"         value="<?php echo $row['size']; ?>">
      <input type="hidden" name="pricer_per_hr" value="<?php echo $row['pricer_per_hr']; ?>">
      <input type="hidden" name="is_active"    value="<?php echo $row['is_active']; ?>">

      <!-- Read-only info -->
      <label>Location</label>
      <input type="text" value="<?php echo htmlspecialchars($row['location']); ?>" disabled>

      <label>Size</label>
      <input type="text" value="<?php echo ucfirst($row['size']); ?>" disabled>

      <label>Price per Hour</label>
      <input type="text" value="₱<?php echo number_format($row['pricer_per_hr'], 2); ?>" disabled>

      <!-- Staff: only Maintenance / Out of Service -->
      <label>Status <span style="font-size:11px; color:#94a3b8;">(staff can only set to Out of Service)</span></label>
      <select name="status">
        <option value="out_of_service" <?php echo $row['status']=='out_of_service' ? 'selected' : ''; ?>>Out of Service / Maintenance</option>
        <option value="available"      <?php echo $row['status']=='available'      ? 'selected' : ''; ?>>Available</option>
      </select>
    <?php endif; ?>

    <button type="submit">Update Locker</button>
  </form>
</div>

</body>
</html>