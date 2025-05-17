<?php
// php/logout.php
session_start();

header('Content-Type: application/json');

// Unset all of the session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
$destroyed = session_destroy();

if ($destroyed) {
    echo json_encode(['success' => 'Logout successful. Redirecting to home...']);
} else {
    // This case is rare if a session was indeed active
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Logout failed. Could not destroy session.']);
}
exit; // Good practice to exit after sending response
?>