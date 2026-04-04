<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';

session_start();

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