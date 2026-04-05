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
else $id = (int) $_GET['id'];

// Spec: Lockers with active reservations cannot be deleted
$active_check = mysqli_query($conn, "SELECT rsvp_id FROM rsvp_details WHERE locker_id=$id AND status='active' LIMIT 1");
if (mysqli_num_rows($active_check) > 0) {
    echo "<script>
            alert('Cannot delete — this locker has an active reservation.');
            window.location.href='dashboard.php';
          </script>";
    exit();
}

$sql = "DELETE FROM locker_rsvp WHERE locker_id=$id";

if (mysqli_query($conn, $sql)) {
    header("Location: dashboard.php");
} else {
    echo "Error: " . mysqli_error($conn);
}

?>