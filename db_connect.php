<?php
$host = "localhost";
$db_name = "locker_reservation";
$username = "root";
$password = "";

// Create connection (procedural)
$conn = mysqli_connect($host, $username, $password, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>