<?php
// modules/auth/api/login_process.php

// 1. Load Configuration
require_once '../../../config/config.php';
require_once BASE_PATH . '/core/ApiHelper.php';

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

    // Call API Gateway
    $ch = curl_init(GATEWAY_URL . 'auth/login');
    $payload = json_encode(['email' => $email, 'password' => $password]);
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $data = json_decode($response, true);

    if ($httpCode === 200 && isset($data['token'])) {
        // Success: Set Session Variables
        $_SESSION['user_id'] = $data['user']['user_id'];
        $_SESSION['user_name'] = $data['user']['user_name'];
        $_SESSION['user_role'] = $data['user']['user_role'];
        $_SESSION['jwt_token'] = $data['token']; // Store JWT for future API calls

        // Set a one-time login success toast message for the dashboard
        $_SESSION['login_success'] = "Welcome back, " . $data['user']['user_name'] . "!";

        // Redirect to Dashboard
        header("Location: " . BASE_URL . "dashboard.php");
        exit();
    } else {
        // Error handling
        $error = $data['error'] ?? 'Login failed. Please try again.';
        header("Location: " . BASE_URL . "index.php?error=" . urlencode($error) . "&email=" . urlencode($email));
        exit();
    }
} else {
    // Direct Access
    header("Location: " . BASE_URL . "index.php");
    exit();
}
?>