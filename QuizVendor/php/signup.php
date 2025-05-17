<?php
session_start(); // Good practice to start session, though not strictly used in this script yet
require 'db.php'; // Adjust path if db.php is in a different location relative to php/signup.php

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read JSON input
$input_data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid JSON payload: ' . json_last_error_msg()]);
    exit;
}

// Sanitize input (using trim is good, further sanitization might be needed depending on context)
$form_full_name = trim($input_data['full_name'] ?? ''); // This comes from the "Full Name" field
$email = trim($input_data['email'] ?? '');
$password = $input_data['password'] ?? '';

// Validate input
if (empty($form_full_name) || empty($email) || empty($password)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Please fill all fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

if (strlen($password) < 6) { // Example: Enforce minimum password length
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 6 characters long']);
    exit;
}

try {
    // Check if email already exists
    // The 'users' table uses 'user_id' as PK, not 'id'
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    if (!$stmt) throw new Exception("Prepare failed (check email): " . $conn->error);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'Email already registered']);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Check if username (full_name) already exists - since username is UNIQUE in DB
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    if (!$stmt) throw new Exception("Prepare failed (check username): " . $conn->error);
    $stmt->bind_param("s", $form_full_name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['error' => 'This name is already taken. Please choose another.']);
        $stmt->close();
        exit;
    }
    $stmt->close();


    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    if ($password_hash === false) {
        throw new Exception("Password hashing failed.");
    }

    // Insert user: Store form's "full_name" into the database's "username" column
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    if (!$stmt) throw new Exception("Prepare failed (insert user): " . $conn->error);
    
    $stmt->bind_param("sss", $form_full_name, $email, $password_hash);

    if ($stmt->execute()) {
        http_response_code(201); // Created
        echo json_encode(['success' => 'User registered successfully. You can now login.']);
    } else {
        throw new Exception("Failed to register user: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    // Log the detailed error on the server for administrators
    error_log("Signup Error: " . $e->getMessage());
    echo json_encode(['error' => 'An error occurred on the server. Please try again later.']);
    // For development, you might want to see the specific error:
    // echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>