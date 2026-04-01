<?php
include 'db_connect.php';

$id = $_GET['id'];

$sql = "DELETE FROM locker_rsvp WHERE locker_id='$id'";

if (mysqli_query($conn, $sql)) {
    header("Location: dashboard.php");
} else {
    echo "Error: " . mysqli_error($conn);
}
?>