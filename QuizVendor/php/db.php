<?php
$host = 'localhost';
$user = 'root';
$password = ''; // Usually empty on XAMPP
$dbname = 'quizz_app'; // Your chosen database name

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(503); // Service Unavailable
    die(json_encode(['error' => 'Database connection failed. Please try again later.']));
    // For debugging: die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

if (!$conn->set_charset("utf8mb4")) {
    // Optional: Log error if charset setting fails
    // error_log("Error loading character set utf8mb4: " . $conn->error);
}
?>