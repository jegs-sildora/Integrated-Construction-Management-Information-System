<?php
// modules/auth/api/signup_process.php

// 1. Load Configuration
require_once '../../../config/config.php';

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

    // Call API Gateway
    $ch = curl_init(GATEWAY_URL . 'auth/signup');
    $payload = json_encode([
        'full_name' => $full_name,
        'email' => $email,
        'password' => $password
    ]);
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $data = json_decode($response, true);

    if ($httpCode === 201) {
        // SUCCESS: Redirect back to login with success message
        header("Location: " . BASE_URL . "index.php?success=" . urlencode($data['message']));
        exit();
    } else {
        // ERROR: Redirect with the error from Gateway
        $error = $data['error'] ?? 'Registration failed. Please try again.';
        header("Location: " . BASE_URL . "index.php?error=" . urlencode($error) . "&signup_name=" . urlencode($full_name));
        exit();
    }
} else {
    // If accessed directly without POST, redirect back to index
    header("Location: " . BASE_URL . "index.php");
    exit();
}
?>