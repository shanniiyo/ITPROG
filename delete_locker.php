<?php
session_start();

include 'db_connect.php';
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] != 'sys_admin') {

    echo "<script>
            alert('Access Denied: You do not have permission to do this action.');
            window.location.href='dashboard.php';
          </script>";
    exit();
}
else $id = $_GET['id'];

$sql = "DELETE FROM locker_rsvp WHERE locker_id='$id'";

if (mysqli_query($conn, $sql)) {
    header("Location: dashboard.php");
} else {
    echo "Error: " . mysqli_error($conn);
}

?>