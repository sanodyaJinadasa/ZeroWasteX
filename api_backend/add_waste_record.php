<?php
include '../includes_backend/db_connect.php';
include '../includes_backend/functions.php';
include '../includes_backend/auth.php'; // makes sure user is logged in

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    
    if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'driver') {
        jsonResponse(false, "Access denied. Only drivers and admins can update waste records.");
        exit;
    }

    // Get values
    $user_id = $_SESSION['user_id'];
    $category_id = (int)$_POST['category_id'];
    $amount = (float)$_POST['amount'];
    $bin_id = (int)$_POST['binid']; 

    // Validation
    if ($user_id <= 0 || $category_id <= 0 || $amount <= 0 || $bin_id <= 0) {
        jsonResponse(false, "All fields are required.");
        exit;
    }

    // Check waste category exists
    $check_cat = $conn->prepare("SELECT * FROM Waste_Category WHERE Category_ID = ?");
    $check_cat->bind_param("i", $category_id);
    $check_cat->execute();
    if ($check_cat->get_result()->num_rows === 0) {
        jsonResponse(false, "Invalid waste category.");
        exit;
    }

    // Find latest valid Collection_ID (if exists)
    $find_collection = $conn->prepare("
        SELECT Collection_ID 
        FROM Collection 
        WHERE User_ID = ? 
          AND FIND_IN_SET(?, Bin_IDs)
        ORDER BY Collection_Date DESC, Collection_Time DESC 
        LIMIT 1
    ");
    $find_collection->bind_param("ii", $user_id, $bin_id);
    $find_collection->execute();
    $result = $find_collection->get_result();

    if ($row = $result->fetch_assoc()) {
        $collection_id = $row['Collection_ID']; 
    } else {
        $collection_id = null;
    }

    // Insert waste record (collection_id will be NULL if not found)
    $sql = $conn->prepare("
        INSERT INTO User_Waste_Record (User_ID, Category_ID, Amount_KG, Bin_ID, Collection_ID, Updated_At)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $sql->bind_param("iidii", $user_id, $category_id, $amount, $bin_id, $collection_id);

    if ($sql->execute()) {

        // Update bin status since waste was collected or recorded
        $update_status = $conn->prepare("UPDATE Bin_Status SET Status = 'Action has been taken.' WHERE Bin_ID = ?");
        $update_status->bind_param("i", $bin_id);
        $update_status->execute();

        jsonResponse(true, "Waste record added successfully.");
    } else {
        jsonResponse(false, "Database Error: " . $conn->error);
    }
} else {
    jsonResponse(false, "Invalid request method.");
}


?>
