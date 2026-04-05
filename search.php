<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';
include 'mailer.php';

// Auto-expire reservations that have passed their end_time
// First, find reservations that are about to expire (within 2 hours) and haven't been reminded yet
// We use a 'expiration_reminder' check — only send once per reservation
$remind_sql = "SELECT rd.rsvp_id, rd.user_id, rd.end_time, rd.created_at,
                      u.email, u.full_name
               FROM rsvp_details rd
               JOIN users u ON rd.user_id = u.user_id
               WHERE rd.status = 'active'
                 AND rd.end_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR)
                 AND rd.rsvp_id NOT IN (
                   SELECT rsvp_id FROM notifications
                   WHERE type = 'expiration_reminder'
                   AND rsvp_id IS NOT NULL
                 )";
$remind_res = mysqli_query($conn, $remind_sql);
if ($remind_res && mysqli_num_rows($remind_res) > 0) {
    while ($row = mysqli_fetch_assoc($remind_res)) {
        $rsvp_num = 'SL-' . date('Y', strtotime($row['created_at'])) . '-' . str_pad($row['rsvp_id'], 6, '0', STR_PAD_LEFT);
        notify_expiration(
            $conn,
            $row['user_id'],
            $row['rsvp_id'],
            $row['email'],
            $row['full_name'],
            $rsvp_num,
            date('M d, Y h:i A', strtotime($row['end_time']))
        );
    }
}

// Now expire reservations past their end_time
$conn->query("UPDATE rsvp_details SET status='expired' WHERE status='active' AND end_time < NOW()");

// Free up lockers that no longer have any active reservation
$conn->query("UPDATE locker_rsvp SET status='available'
              WHERE locker_id NOT IN (
                SELECT locker_id FROM rsvp_details WHERE status='active'
              )
              AND status='occupied'");

// GET FILTER VALUES
$location = isset($_GET['location']) ? mysqli_real_escape_string($conn, $_GET['location']) : '';
$size     = isset($_GET['size'])     ? mysqli_real_escape_string($conn, $_GET['size'])     : '';

// SQL QUERY — only available, active lockers
$sql = "SELECT * FROM locker_rsvp WHERE status='available' AND is_active=1";

if ($location != '') {
    $sql .= " AND location='$location'";
}
if ($size != '') {
    $sql .= " AND size='$size'"; // DB stores lowercase: small, medium, large
}

$result = $conn->query($sql);

// Fetch all distinct locations dynamically for the dropdown
$loc_res   = $conn->query("SELECT DISTINCT location FROM locker_rsvp WHERE is_active=1 ORDER BY location");
$locations = [];
while ($loc = $loc_res->fetch_assoc()) {
    $locations[] = $loc['location'];
}

$size_labels  = ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'];
$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style(itprog).css">
</head>
<body>

<?php include 'navbar_client.php'; ?>

<div class="container">

<h1>Find Your Perfect Locker</h1>

<!-- FILTER FORM -->
<form method="GET">
  <div class="filters">

    <select name="location">
      <option value="">All Locations</option>
      <?php foreach ($locations as $loc): ?>
        <option value="<?php echo htmlspecialchars($loc); ?>"
          <?php echo ($location == $loc) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($loc); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="size">
      <option value="">All Sizes</option>
      <option value="small"  <?php echo ($size == 'small')  ? 'selected' : ''; ?>>Small</option>
      <option value="medium" <?php echo ($size == 'medium') ? 'selected' : ''; ?>>Medium</option>
      <option value="large"  <?php echo ($size == 'large')  ? 'selected' : ''; ?>>Large</option>
    </select>

    <button type="submit">Search</button>
  </div>
</form>

<!-- RESULTS -->
<div class="locker-grid">

<?php
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
?>

  <div class="locker-card">
    <h3>Locker <?php echo $row['locker_id']; ?></h3>
    <span class="badge"><?php echo ucfirst($row['status']); ?></span>
    <p>📍 <?php echo htmlspecialchars($row['location']); ?></p>
    <p>Size: <?php echo $size_labels[$row['size']]; ?></p>
    <h2>₱<?php echo $row['pricer_per_hr']; ?>/hour</h2>

    <?php if ($is_logged_in): ?>
      <a href="reserve.php?locker_id=<?php echo $row['locker_id']; ?>">
        <button>Reserve</button>
      </a>
    <?php else: ?>
      <a href="login.php">
        <button>Sign in to Reserve</button>
      </a>
    <?php endif; ?>
  </div>

<?php
    }
} else {
    echo "<p>No lockers found.</p>";
}
?>

</div>

</div>

</body>
</html>