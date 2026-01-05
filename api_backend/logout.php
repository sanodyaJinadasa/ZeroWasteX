<?php
session_start(); // Start or resume the session

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Delete the PHP session cookie (optional but recommended)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Return a JSON response
header("Content-Type: application/json");
echo json_encode([
    "success" => true,
    "message" => "You have been logged out successfully."
]);
?>
