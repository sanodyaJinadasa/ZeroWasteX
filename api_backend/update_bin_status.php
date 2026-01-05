<?php
error_reporting(E_ALL); // Enable all errors for debugging
ini_set('display_errors', 1);

include '../includes_backend/db_connect.php';
include '../includes_backend/functions.php';

header('Content-Type: application/json');

// GET request: fetch all bins
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $sql = "SELECT Bin_ID, Location, Variety FROM Bin";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $bins = [];
        while ($row = $result->fetch_assoc()) $bins[] = $row;
        echo json_encode(["success" => true, "data" => $bins]);
    } else {
        echo json_encode(["success" => false, "message" => "No bins found."]);
    }
    exit;
}

// POST request: update bin status
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bin_id = (int)($_POST['bin_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? '');
    $action = isset($_POST['action']) && trim($_POST['action']) !== ''
        ? sanitize($_POST['action'])
        : 'Status updated';

    if (!$bin_id || !$status) {
        echo json_encode(["success" => false, "message" => "Bin ID or status missing."]);
        exit;
    }

    // Check if bin exists
    $check = $conn->prepare("SELECT Bin_ID FROM Bin WHERE Bin_ID = ?");
    $check->bind_param("i", $bin_id);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    if (!$exists) {
        echo json_encode(["success" => false, "message" => "Invalid Bin ID."]);
        exit;
    }

    // Always insert a new status record
    $insert = $conn->prepare("INSERT INTO Bin_Status (Bin_ID, Status, Action) VALUES (?, ?, ?)");
    $insert->bind_param("iss", $bin_id, $status, $action);
    if (!$insert->execute()) {
        echo json_encode(["success"=>false,"message"=>"Insert DB error: ".$insert->error]);
        exit;
    }

    // Notify if full
    if (strtolower($status) === 'full') {
        try { sendFullBinNotification($conn, $bin_id); } 
        catch(Exception $e){ error_log("Notification error: ".$e->getMessage()); }
    }

    echo json_encode(["success"=>true,"message"=>"Bin status updated successfully."]);
    exit;
}


function sendFullBinNotification($conn, $bin_id) {
    $bin_query = $conn->prepare("SELECT Location, Variety FROM Bin WHERE Bin_ID = ?");
    $bin_query->bind_param("i", $bin_id);
    $bin_query->execute();
    $bin = $bin_query->get_result()->fetch_assoc();
    if (!$bin) return;

    $message = "⚠️ Bin at {$bin['Location']} ({$bin['Variety']}) is now FULL.";

    $user_query = $conn->query("SELECT User_ID FROM User WHERE Role IN ('admin', 'staff')");
    $insert_stmt = $conn->prepare("INSERT INTO Notifications (User_ID, Message) VALUES (?, ?)");
    while ($user = $user_query->fetch_assoc()) {
        $uid = $user['User_ID'];
        $insert_stmt->bind_param("is", $uid, $message);
        $insert_stmt->execute();
    }
}
