<?php
// php/get_history.php
session_start();
require 'db.php'; // Assuming db.php is in the parent directory (e.g., Project/)

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'User not logged in. Please login to view history.']);
    exit;
}

$taker_user_id = $_SESSION['user_id'];
$history = [];

try {
    // Prepare SQL to fetch quiz attempts and join with quizzes table for the title
    $stmt = $conn->prepare("
        SELECT
            q.title AS quiz_title,
            qa.attempt_datetime,
            qa.score,
            qa.total_correct_answers,
            qa.total_questions_answered,
            qa.attempt_id 
        FROM
            quiz_attempts qa
        JOIN
            quizzes q ON qa.quiz_id = q.quiz_id
        WHERE
            qa.taker_user_id = ?
        ORDER BY
            qa.attempt_datetime DESC
    ");

    if (!$stmt) {
        throw new Exception("Database prepare statement failed: " . $conn->error);
    }

    $stmt->bind_param("i", $taker_user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Database execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Format date for better readability
        $date = new DateTime($row['attempt_datetime']);
        $row['attempt_date_formatted'] = $date->format('Y-m-d H:i'); // e.g., 2025-05-17 14:30
        $history[] = $row;
    }
    
    $stmt->close();
    $conn->close();

    echo json_encode(['success' => true, 'history' => $history]);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    // Log the detailed error on the server for administrators
    error_log("Error in get_history.php: " . $e->getMessage());
    echo json_encode(['error' => 'An error occurred while fetching your quiz history.']);
}
?>