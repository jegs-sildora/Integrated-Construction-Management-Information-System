<?php
// modules/auth/api/signup_process.php

// 1. Load Configuration
require_once '../../../config/config.php';
require_once BASE_PATH . '/config/database.php';

// 2. Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Handle Form Submission
if (($_SERVER["REQUEST_METHOD"] ?? 'GET') === 'POST' && isset($_POST['signup'])) {
    
    // Sanitize Inputs
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validation: Check if empty
    if (empty($full_name) || empty($email) || empty($password)) {
        header("Location: " . BASE_URL . "index.php?error=All fields are required&signup_name=" . urlencode($full_name));
        exit();
    }

    // A. Check if email already exists
    $check = $conn->prepare("SELECT user_id FROM icmis_users WHERE email = ?");
    if ($check === false) {
        // Redirect with DB error instead of white screen
        header("Location: " . BASE_URL . "index.php?error=Database error: " . urlencode($conn->error));
        exit();
    }
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        header("Location: " . BASE_URL . "index.php?error=Email already exists&signup_name=" . urlencode($full_name));
        exit();
    }
    $check->close();

    // B. Create New User
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'Admin'; // Default role for new signups

    $stmt = $conn->prepare("INSERT INTO icmis_users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
    if ($stmt === false) {
        header("Location: " . BASE_URL . "index.php?error=Database error: " . urlencode($conn->error));
        exit();
    }
    $stmt->bind_param("ssss", $full_name, $email, $hashed_password, $role);

    // C. Execute and Redirect
    if ($stmt->execute()) {
        // SUCCESS: Redirect back to login with success message for the toast
        header("Location: " . BASE_URL . "index.php?success=Account created successfully.");
        exit();
    } else {
        // ERROR: Redirect with the specific SQL error for the toast
        header("Location: " . BASE_URL . "index.php?error=Registration failed: " . urlencode($stmt->error));
        exit();
    }
    $stmt->close();

} else {
    // If accessed directly without POST, redirect back to index
    header("Location: " . BASE_URL . "index.php");
    exit();
}
?>