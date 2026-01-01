<?php
// modules/procurement/php/db_connect.php

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "icmis_procurement_inventory_db";

// Create connection
$conn_proc = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn_proc->connect_error) {
    // Return a JSON error and stop execution if connection fails
    die(json_encode(["error" => "Database connection failed: " . $conn_proc->connect_error]));
}

// IMPORTANT: No "echo" here. It corrupts JSON responses.
?>