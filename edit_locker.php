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
  <?php elseif ($role == 'manager'): ?>
    <p style="font-size:13px; color:#64748b; text-align:center; margin-bottom:16px;">
      As manager, you can update pricing and size only.
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
      <input type="number" step="0.01" name="pricer_per_hr" value="<?php echo $row['pricer_per_hr']; ?>" required>

      <label>Status</label>
      <select name="status">
        <option value="available"      <?php echo $row['status']=='available'      ? 'selected' : ''; ?>>Available</option>
        <option value="occupied"       <?php echo $row['status']=='occupied'       ? 'selected' : ''; ?>>Occupied</option>
        <option value="out_of_service" <?php echo $row['status']=='out_of_service' ? 'selected' : ''; ?>>Out of Service</option>
      </select>

      <label>Active</label>
      <select name="is_active">
        <option value="1" <?php echo $row['is_active'] ? 'selected' : ''; ?>>Yes</option>
        <option value="0" <?php echo !$row['is_active'] ? 'selected' : ''; ?>>No (Disabled)</option>
      </select>

    <?php elseif ($role == 'manager'): ?>
      <!-- manager: pricing and size only, all other fields hidden -->
      <input type="hidden" name="location"  value="<?php echo htmlspecialchars($row['location']); ?>">
      <input type="hidden" name="status"    value="<?php echo $row['status']; ?>">
      <input type="hidden" name="is_active" value="<?php echo $row['is_active']; ?>">

      <!-- Read-only info -->
      <label>Location</label>
      <input type="text" value="<?php echo htmlspecialchars($row['location']); ?>" disabled>

      <label>Status</label>
      <input type="text" value="<?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>" disabled>

      <!-- Editable: size and price -->
      <label>Size</label>
      <select name="size">
        <option value="small"  <?php echo $row['size']=='small'  ? 'selected' : ''; ?>>Small</option>
        <option value="medium" <?php echo $row['size']=='medium' ? 'selected' : ''; ?>>Medium</option>
        <option value="large"  <?php echo $row['size']=='large'  ? 'selected' : ''; ?>>Large</option>
      </select>

      <label>Price per Hour (₱)</label>
      <input type="number" step="0.01" name="pricer_per_hr" value="<?php echo $row['pricer_per_hr']; ?>" required>

    <?php else: ?>
      <!-- staff: status only -->
      <input type="hidden" name="location"    value="<?php echo htmlspecialchars($row['location']); ?>">
      <input type="hidden" name="size"        value="<?php echo $row['size']; ?>">
      <input type="hidden" name="pricer_per_hr" value="<?php echo $row['pricer_per_hr']; ?>">
      <input type="hidden" name="is_active"   value="<?php echo $row['is_active']; ?>">

      <label>Location</label>
      <input type="text" value="<?php echo htmlspecialchars($row['location']); ?>" disabled>

      <label>Size</label>
      <input type="text" value="<?php echo ucfirst($row['size']); ?>" disabled>

      <label>Price per Hour</label>
      <input type="text" value="₱<?php echo number_format($row['pricer_per_hr'], 2); ?>" disabled>

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