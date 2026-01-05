<?php
// modules/procurement/php/db_connect.php

$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "icmis_procurement_inventory_db"; 

// Create connection
$conn_proc = new mysqli($servername, $username, $password, $dbname);

// FIX: Do NOT use die() here. Let the including script handle the error.
if ($conn_proc->connect_error) {
    // Just log it or set a variable, don't kill the output buffer
    error_log("Procurement DB Connection Failed: " . $conn_proc->connect_error);
}
?>