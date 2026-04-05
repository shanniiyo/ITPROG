<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'db_connect.php';

// Only sys_admin can add lockers
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] != 'sys_admin') {
    echo "<script>
            alert('Access Denied: You do not have permission to do this action.');
            window.location.href='dashboard.php';
          </script>";
    exit();
}

$location = mysqli_real_escape_string($conn, $_POST['location']);
$size = $_POST['size'];
$status = $_POST['status'];
$price = $_POST['price'];

$sql = "INSERT INTO locker_rsvp (location, size, status, pricer_per_hr)
        VALUES ('$location', '$size', '$status', '$price')";

if (mysqli_query($conn, $sql)) {
    header("Location: dashboard.php");
} else {
    echo "Error: " . mysqli_error($conn);
}
?>