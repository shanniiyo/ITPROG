<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connect.php';

$id = $_POST['id'];
$location = mysqli_real_escape_string($conn, $_POST['location']);
$size = $_POST['size'];
$status = $_POST['status'];
$price = $_POST['pricer_per_hr'];

$sql = "UPDATE locker_rsvp SET location='$location', size='$size', status='$status', pricer_per_hr='$price'
        WHERE locker_id='$id'";

if (mysqli_query($conn, $sql)) {
    header("Location: dashboard.php");
} else {
    echo "Error: " . mysqli_error($conn);
}
?>