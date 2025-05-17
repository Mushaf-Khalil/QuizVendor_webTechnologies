<?php
session_start(); // Essential for login
require 'db.php'; // Adjust path as needed

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input_data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON payload: ' . json_last_error_msg()]);
    exit;
}

$email = trim($input_data['email'] ?? '');
$password = $input_data['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

try {
    // Fetch user by email. We need user_id, username (which holds full_name), and password_hash.
    $stmt = $conn->prepare("SELECT user_id, username, password_hash FROM users WHERE email = ?");
    if (!$stmt) throw new Exception("Prepare failed (fetch user): " . $conn->error);

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username']; // This contains the full name
            $_SESSION['email'] = $email;
            $_SESSION['logged_in'] = true;

            echo json_encode([
                'success' => 'Login successful!',
                'user' => [ // Optionally send some user data back
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'email' => $email
                ]
            ]);
        } else {
            http_response_code(401); // Unauthorized
            echo json_encode(['error' => 'Invalid email or password']);
        }
    } else {
        http_response_code(401); // Unauthorized
        echo json_encode(['error' => 'Invalid email or password']);
    }
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    error_log("Login Error: " . $e->getMessage());
    echo json_encode(['error' => 'An error occurred on the server. Please try again later.']);
    // For development: echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>