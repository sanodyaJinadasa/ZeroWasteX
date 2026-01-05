<?php
session_start();

include '../includes_backend/db_connect.php';
include '../includes_backend/functions.php';
include '../includes_backend/auth.php'; // ensures session exists
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "Session expired. Please log in again."]);
        exit;
    }
    // Use session instead of manual POST
    $user_id = $_SESSION['user_id'];
    $bin_ids = sanitize($_POST['bin_ids']);
    if (empty($bin_ids)) {
        echo json_encode(["success" => false, "message" => "No bins selected."]);
        exit;
    }
    $sql = $conn->prepare("INSERT INTO Collection (User_ID, Bin_IDs)
                           VALUES (?, ?)");
    $sql->bind_param("is", $user_id, $bin_ids);

    if ($sql->execute()) {
        // Set bins to empty after collection
       $conn->query("UPDATE Bin_Status SET Action='The action is scheduled.' WHERE Bin_ID IN ($bin_ids)");

       echo json_encode(["success" => true, "message" => "Collection added successfully."]);
        exit;
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        exit;
    }
}
?>

