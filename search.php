<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';


session_start();

// GET FILTER VALUES
$location = $_GET['location'] ?? '';
$size = $_GET['size'] ?? '';

// SQL QUERY
$sql = "SELECT * FROM locker_rsvp WHERE status='available'";

if ($location != '') {
  $sql .= " AND location='$location'";
}

if ($size != '') {
  $sql .= " AND size='$size'";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="style(itprog).css">
</head>
<body>

<div class="navbar">
   <a href = "index.php"> <h2>SmartLocker</h2> </a>
  <a href = "search.php"> Search lockers </a>
  <a href = ""> My Reservations </a>
  <a href = ""> Notifications </a>
  <a href = "support.php"> Support </a>

  <div class = "nav--right">
  <a href = ""> Account </a>
  <a href = "logout.php"> Log Out </a>
</div>
</div>



<div class="container">

<h1>Find Your Perfect Locker</h1>

<!-- FILTER FORM -->
<form method="GET">
  <div class="filters">

    <select name="location">
      <option value="">All Locations</option>
      <option value="NAIA Terminal 3">NAIA Terminal 3</option>
      <option value="Cebu Airport">Cebu Airport</option>
    </select>

    <select name="size">
      <option value="">All Sizes</option>
      <option value="Small">Small</option>
      <option value="Medium">Medium</option>
      <option value="Large">Large</option>
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
    <span class="badge"><?php echo $row['status']; ?></span>
    <p>📍 <?php echo $row['location']; ?></p>
    <p>Size: <?php echo $row['size']; ?></p>
    <h2>₱<?php echo $row['pricer_per_hr']; ?>/hour</h2>
    <button>Reserve</button>
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