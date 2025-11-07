<?php
$host = "localhost";
$user = "root"; // my MySQL username
$pass = ""; // my MySQL password
$db = "studentcollabo_db";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>