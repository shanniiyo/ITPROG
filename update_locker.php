<?php
session_start();
include 'db_connect.php';

if (
    !isset($_SESSION['admin_id']) ||
    !in_array($_SESSION['admin_role'], ['sys_admin', 'manager', 'staff'])
) {
    echo "<script>
            alert('Access Denied: You do not have permission to do this action.');
            window.location.href='dashboard.php';
          </script>";
    exit();
}

$role = $_SESSION['admin_role'];
$id   = (int) $_POST['id'];

if ($role == 'sys_admin') {
    // Full update
    $location  = mysqli_real_escape_string($conn, $_POST['location']);
    $size      = $_POST['size'];
    $status    = $_POST['status'];
    $price     = (float) $_POST['pricer_per_hr'];
    $is_active = (int) $_POST['is_active'];

    $sql = "UPDATE locker_rsvp
            SET location='$location', size='$size', status='$status',
                pricer_per_hr=$price, is_active=$is_active
            WHERE locker_id=$id";

} elseif ($role == 'manager') {
    // Manager: size and price only
    $allowed_sizes = ['small', 'medium', 'large'];
    $size  = in_array($_POST['size'], $allowed_sizes) ? $_POST['size'] : 'small';
    $price = (float) $_POST['pricer_per_hr'];

    $sql = "UPDATE locker_rsvp SET size='$size', pricer_per_hr=$price WHERE locker_id=$id";

} else {
    // Staff: status only — only 'available' or 'out_of_service'
    $allowed_statuses = ['available', 'out_of_service'];
    $status = in_array($_POST['status'], $allowed_statuses) ? $_POST['status'] : 'out_of_service';

    $sql = "UPDATE locker_rsvp SET status='$status' WHERE locker_id=$id";
}

if (mysqli_query($conn, $sql)) {
    header("Location: dashboard.php");
} else {
    echo "Error: " . mysqli_error($conn);
}
?>