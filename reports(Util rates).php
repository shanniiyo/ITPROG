<?php
include "config.php";

// Dummy summary values for now
$total_lockers = 120;
$occupied_lockers = 78;
$available_lockers = 30;
$maintenance_lockers = 8;
$disabled_lockers = 4;

$active_lockers = $total_lockers - $maintenance_lockers - $disabled_lockers;
$utilization_rate = $active_lockers > 0 ? round(($occupied_lockers / $active_lockers) * 100, 2) : 0;

// Dummy report rows
$report_rows = [
    ["location" => "NAIA Terminal 3", "total" => 120, "occupied" => 78, "available" => 30, "maintenance" => 8, "disabled" => 4, "utilization" => "72.22%"],
    ["location" => "Cebu Airport", "total" => 80, "occupied" => 44, "available" => 28, "maintenance" => 5, "disabled" => 3, "utilization" => "61.97%"],
    ["location" => "Clark Airport", "total" => 60, "occupied" => 39, "available" => 16, "maintenance" => 3, "disabled" => 2, "utilization" => "70.91%"]
];
?>

<!DOCTYPE html>
<html>
<head>
  <title>Utilization Reports</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
  <h2>SmartLocker Admin</h2>
</div>

<div class="container">
  <h1>Utilization Rates & Reports</h1>

  <div class="summary-grid">
    <div class="summary-card">
      <h3>Total Lockers</h3>
      <h2><?php echo $total_lockers; ?></h2>
    </div>

    <div class="summary-card">
      <h3>Occupied Lockers</h3>
      <h2><?php echo $occupied_lockers; ?></h2>
    </div>

    <div class="summary-card">
      <h3>Available Lockers</h3>
      <h2><?php echo $available_lockers; ?></h2>
    </div>

    <div class="summary-card">
      <h3>Utilization Rate</h3>
      <h2><?php echo $utilization_rate; ?>%</h2>
    </div>
  </div>

  <div class="card">
    <h3>Generate Report</h3>
    <form method="GET" class="report-form">
      <select name="report_type">
        <option value="daily">Daily Report</option>
        <option value="weekly">Weekly Report</option>
        <option value="monthly">Monthly Report</option>
      </select>

      <select name="location">
        <option value="">All Locations</option>
        <option value="NAIA Terminal 3">NAIA Terminal 3</option>
        <option value="Cebu Airport">Cebu Airport</option>
        <option value="Clark Airport">Clark Airport</option>
      </select>

      <input type="date" name="start_date">
      <input type="date" name="end_date">

      <button type="submit">Generate</button>
    </form>

    <div style="margin-top: 15px;">
      <button type="button">Export PDF</button>
      <button type="button">Export CSV</button>
    </div>
  </div>

  <div class="card">
    <h3>Locker Utilization Table</h3>
    <table class="report-table">
      <tr>
        <th>Location</th>
        <th>Total Lockers</th>
        <th>Occupied</th>
        <th>Available</th>
        <th>Maintenance</th>
        <th>Disabled</th>
        <th>Utilization Rate</th>
      </tr>

      <?php foreach ($report_rows as $row): ?>
      <tr>
        <td><?php echo $row['location']; ?></td>
        <td><?php echo $row['total']; ?></td>
        <td><?php echo $row['occupied']; ?></td>
        <td><?php echo $row['available']; ?></td>
        <td><?php echo $row['maintenance']; ?></td>
        <td><?php echo $row['disabled']; ?></td>
        <td><?php echo $row['utilization']; ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

</body>
</html>