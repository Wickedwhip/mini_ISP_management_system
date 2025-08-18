<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "mini_ISP_management_system";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
