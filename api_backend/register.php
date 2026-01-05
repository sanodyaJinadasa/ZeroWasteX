<?php
header("Content-Type: application/json");
session_start(); // start session normally
include '../includes_backend/db_connect.php';
include '../includes_backend/functions.php';
//include '../includes_backend/auth.php'; // To ensure admin-only access

// to Check if logged-in user is admin
    if ($_SESSION['role'] !== 'admin') {
        jsonResponse(false, "Access denied. Only admins can add new users.");
        exit;
    }


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    
    // Collect input
    $username = sanitize($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $age = (int)$_POST['age'];
    $contact = sanitize($_POST['contact']);
    $role = sanitize($_POST['role']); // admin, officer, driver

    // Check username uniqueness
    $check = $conn->prepare("SELECT * FROM User WHERE Username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {
        jsonResponse(false, "Username already exists.");
    } else {
        // Insert new user
        $sql = $conn->prepare("INSERT INTO User (Username, Password, Name, Email, Age, Contact, Role)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $sql->bind_param("sssssss", $username, $password, $name, $email, $age, $contact, $role);
        
        if ($sql->execute()) {
            jsonResponse(true, "New user added successfully by admin.");
        } else {
            jsonResponse(false, "Database error: " . $conn->error);
        }
    }
}
?>
