<?php
require_once '../includes_backend/db_connect.php';

// Function to get the total number of bins
function getTotalBins() {
    global $conn;
    $sql = "SELECT COUNT(*) AS total_bins FROM bin";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['total_bins'];
    }
    return 0;
}

// Function to get the number of bins by their latest status
function getBinsByStatus($status) {
    global $conn;
    $count = 0;

    // Get all unique bin IDs
    $sql_bins = "SELECT bin_ID FROM bin";
    $result_bins = $conn->query($sql_bins);

    if ($result_bins && $result_bins->num_rows > 0) {
        while ($bin_row = $result_bins->fetch_assoc()) {
            $bin_ID = $bin_row['bin_ID'];

            // Get the latest status for the current bin_ID
            $sql_latest_status = "SELECT status FROM bin_status WHERE bin_ID = ? ORDER BY status_ID DESC LIMIT 1";
            $stmt = $conn->prepare($sql_latest_status);
            $stmt->bind_param("i", $bin_ID);
            $stmt->execute();
            $result_latest_status = $stmt->get_result();

            if ($result_latest_status && $result_latest_status->num_rows > 0) {
                $status_row = $result_latest_status->fetch_assoc();
                if ($status_row['status'] == $status) {
                    $count++;
                }
            }
            $stmt->close();
        }
    }
    return $count;
}
// Function: Get total collections today
function getTodayCollections() {
    global $conn;
    date_default_timezone_set('Asia/Colombo'); // adjust as needed
    $today = date('Y-m-d');

    $sql = "SELECT COUNT(*) AS total_collections
            FROM User_Waste_Record
            WHERE DATE(Updated_At) = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ? $row['total_collections'] : 0;
}

// Function to get the number of full bins
function getNumberOfFullBins() {
    return getBinsByStatus('full');
}

// Function to get the number of half-full bins
function getNumberOfHalfFullBins() {
    return getBinsByStatus('half');
}
// Example usage (you can remove or modify this based on how you want to use these functions)
// This part will execute when the file is accessed directly
if (basename($_SERVER['PHP_SELF']) == 'admin_functiona.php') {
    header('Content-Type: application/json');
    $response = [];

    $response['total_bins'] = getTotalBins();
    $response['full_bins'] = getNumberOfFullBins();
    $response['half_full_bins'] = getNumberOfHalfFullBins();
    $response['today_collections'] = getTodayCollections();
    

    echo json_encode($response);
}

?>