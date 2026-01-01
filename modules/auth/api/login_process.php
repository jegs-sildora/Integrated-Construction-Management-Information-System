<?php
// modules/auth/api/login_process.php

// 1. Load Configuration
require_once '../../../config/config.php';
require_once BASE_PATH . '/config/database.php';

// 2. Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Handle Form Submission
if (($_SERVER["REQUEST_METHOD"] ?? 'GET') === 'POST' && isset($_POST['login'])) {
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validation
    if (empty($email) || empty($password)) {
        header("Location: " . BASE_URL . "index.php?error=All fields are required&email=" . urlencode($email));
        exit();
    }

    // Database Check
    $stmt = $conn->prepare("SELECT user_id, full_name, password, role FROM users WHERE email = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Verify Password
        if (password_verify($password, $row['password'])) {
            // Success: Set Session Variables
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['user_name'] = $row['full_name'];
            $_SESSION['user_role'] = $row['role'];

            // Redirect to Dashboard
            header("Location: " . BASE_URL . "dashboard.php");
            exit();
        } else {
            // Incorrect Password
            header("Location: " . BASE_URL . "index.php?error=Incorrect password&email=" . urlencode($email));
            exit();
        }
    } else {
        // User not found
        header("Location: " . BASE_URL . "index.php?error=User not found&email=" . urlencode($email));
        exit();
    }
    $stmt->close();

} else {
    // Direct Access
    header("Location: " . BASE_URL . "index.php");
    exit();
}
?>