<?php
session_start();

/* 
   DETERMINE USER TYPE
 */

if (isset($_SESSION['user_id'])) {
    $redirect = "login.php";
} else {
    $redirect = "admin_login.php";
}

session_unset();
session_destroy();

header("Location: $redirect");
exit();
?>