<?php
include '../includes_backend/db_connect.php';
include '../includes_backend/functions.php';
session_start();

// FORGOT PASSWORD 
if (isset($_POST['action']) && $_POST['action'] === "reset_password") {

    $username = sanitize($_POST['username']);
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $sql = $conn->prepare("SELECT * FROM User WHERE Username = ?");
    $sql->bind_param("s", $username);
    $sql->execute();
    $result = $sql->get_result();

    if ($result->num_rows === 1) {
        $update = $conn->prepare("UPDATE User SET Password = ? WHERE Username = ?");
        $update->bind_param("ss", $new_password, $username);
        $update->execute();

        jsonResponse(true, "Password updated successfully.");
    } else {
        jsonResponse(false, "Username not found.");
    }
    exit;
}

//  NORMAL LOGIN 
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    $sql = $conn->prepare("SELECT * FROM User WHERE Username = ?");
    $sql->bind_param("s", $username);
    $sql->execute();
    $result = $sql->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['Password'])) {

            $_SESSION['user_id'] = $user['User_ID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['role'] = $user['Role'];
            $_SESSION['name'] = $user['Name'];

            jsonResponse(true, "Login successful", [
                "user_id" => $user['User_ID'],
                "username" => $user['Username'],
                "role" => $user['Role'],
                "name" => $user['Name']
            ]);
        } else {
            jsonResponse(false, "Invalid password.");
        }

    } else {
        jsonResponse(false, "User not found.");
    }
}
?>
