<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

include '../includes_backend/db_connect.php';
include '../includes_backend/functions.php';

// -------------------- Get Request Params --------------------
$report = $_GET['report'] ?? '';
$period = $_GET['period'] ?? '';
$location = $_GET['location'] ?? '';
$date = $_GET['date'] ?? '';
$year = $_GET['year'] ?? '';
$month = $_GET['month'] ?? '';
$week = $_GET['week'] ?? '';

// -------------------- Helper Functions --------------------
function sendResponse($success, $data = [], $message = '') {
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

// Convert week number to start/end dates
function getStartAndEndDate($year, $weekNumber){
    $dto = new DateTime();
    $dto->setISODate($year, $weekNumber);
    $start = $dto->format('Y-m-d');
    $dto->modify('+6 days');
    $end = $dto->format('Y-m-d');
    return [$start, $end];
}

// -------------------- 1. Load All Locations --------------------
if ($report == 'uniqueAreas') {

    $sql = "SELECT Location_Name AS Location FROM Locations ORDER BY Location_Name ASC";
    $result = $conn->query($sql);

    if ($result) {
        $areas = [];
        while ($row = $result->fetch_assoc()) {
            $areas[] = $row;
        }
        sendResponse(true, $areas);
    } else {
        sendResponse(false, [], "Query error: ".$conn->error);
    }
}

// -------------------- 2. Category-wise Waste Amount for Location --------------------
elseif ($report == 'categoryPerArea') {

    if (empty($location)) sendResponse(false, [], 'Location is required');

    $sql = "
        SELECT 
            loc.Location_Name AS Area,
            wc.Category_Name,
            SUM(uwr.Amount_KG) AS Total_KG
        FROM User_Waste_Record uwr
        JOIN Bin b ON uwr.Bin_ID = b.Bin_ID
        JOIN Locations loc ON b.Location = loc.Location_Name
        JOIN Waste_Category wc ON uwr.Category_ID = wc.Category_ID
        WHERE loc.Location_Name = '$location'
    ";

    // Apply period filters
    if ($period == 'day' && !empty($date)) {
        $sql .= " AND DATE(uwr.Updated_At) = '$date'";
    }
    elseif ($period == 'week' && !empty($year) && !empty($week)) {
        list($startDate, $endDate) = getStartAndEndDate($year, $week);
        $sql .= " AND DATE(uwr.Updated_At) BETWEEN '$startDate' AND '$endDate'";
    }
    elseif ($period == 'month' && !empty($year) && !empty($month)) {
        $sql .= " AND YEAR(uwr.Updated_At) = $year AND MONTH(uwr.Updated_At) = $month";
    }
    elseif ($period == 'year' && !empty($year)) {
        $sql .= " AND YEAR(uwr.Updated_At) = $year";
    }

    $sql .= " 
        GROUP BY wc.Category_Name, loc.Location_Name 
        ORDER BY wc.Category_Name ASC
    ";

    $result = $conn->query($sql);

    if ($result) {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        if (empty($data)) sendResponse(true, [], 'No data found for this period');

        sendResponse(true, $data);
    }
    else {
        sendResponse(false, [], "Query error: ".$conn->error);
    }
}

else {
    sendResponse(false, [], 'Invalid report type');
}

?>




