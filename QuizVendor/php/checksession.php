<?php
session_start(); // Must be called before accessing any session variables

header('Content-Type: application/json');

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_id'])) {
    // User is logged in
    // You can send back any relevant user information stored in the session
    echo json_encode([
        'loggedIn' => true,
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? null, // The 'username' from users table (stores full name)
        'email' => $_SESSION['email'] ?? null
    ]);
} else {
    // User is not logged in
    http_response_code(401); // Unauthorized (optional, but good practice for API-like checks)
    echo json_encode(['loggedIn' => false]);
}
?>