<?php 
// Set timezone to Manila
date_default_timezone_set('Asia/Manila');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "icmis_budget";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}