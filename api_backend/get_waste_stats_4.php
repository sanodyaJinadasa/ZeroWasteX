<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

include '../includes_backend/db_connect.php';

// -------------------- Helper --------------------
function sendResponse($success, $data = [], $message = '') {
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

// Convert ISO week to start/end dates
function getStartAndEndDate($year, $weekNumber){
    $dto = new DateTime();
    $dto->setISODate($year, $weekNumber);
    $start = $dto->format('Y-m-d');
    $dto->modify('+6 days');
    $end = $dto->format('Y-m-d');
    return [$start, $end];
}

// -------------------- Request Params --------------------
$type   = $_GET['type'] ?? '';
$period = $_GET['period'] ?? '';
$area   = $_GET['area'] ?? '';
$date   = $_GET['date'] ?? '';
$year   = $_GET['year'] ?? '';
$month  = $_GET['month'] ?? '';
$week   = $_GET['week'] ?? '';

// -------------------- Areas --------------------
if($type === 'areas'){
    $res = $conn->query("SELECT Location_Name AS Location FROM Locations ORDER BY Location_Name ASC");
    if(!$res) sendResponse(false, [], $conn->error);

    $areas = [];
    while($row = $res->fetch_assoc()) $areas[] = $row;
    sendResponse(true, $areas);
}

// -------------------- Category Trend --------------------
if($type === 'categoryTrend'){
    $sql = "SELECT wc.Category_Name, SUM(uwr.Amount_KG) AS Total_KG";

    // Compute period label for chart
    if($period === 'day') $sql .= ", DATE(uwr.Updated_At) AS Period";
    elseif($period === 'week') $sql .= ", CONCAT(YEAR(uwr.Updated_At), '-W', WEEK(uwr.Updated_At,1)) AS Period";
    elseif($period === 'month') $sql .= ", DATE_FORMAT(uwr.Updated_At,'%Y-%m') AS Period";
    elseif($period === 'year') $sql .= ", YEAR(uwr.Updated_At) AS Period";

    $sql .= " FROM User_Waste_Record uwr
              JOIN Bin b ON uwr.Bin_ID = b.Bin_ID
              JOIN Waste_Category wc ON uwr.Category_ID = wc.Category_ID";

    $where = [];
    if(!empty($area)) $where[] = "b.Location = '".$conn->real_escape_string($area)."'";

    // Period filters
    if($period === 'day' && !empty($date)) $where[] = "DATE(uwr.Updated_At) = '".$conn->real_escape_string($date)."'";
    if($period === 'week' && !empty($year) && !empty($week)) {
        list($start, $end) = getStartAndEndDate($year, $week);
        $where[] = "DATE(uwr.Updated_At) BETWEEN '$start' AND '$end'";
    }
    if($period === 'month' && !empty($year) && !empty($month)) $where[] = "YEAR(uwr.Updated_At) = $year AND MONTH(uwr.Updated_At) = $month";
    if($period === 'year' && !empty($year)) $where[] = "YEAR(uwr.Updated_At) = $year";

    if(count($where) > 0) $sql .= " WHERE " . implode(" AND ", $where);

    $sql .= " GROUP BY wc.Category_Name";
    if(in_array($period, ['day','week','month','year'])) $sql .= ", Period";
    $sql .= " ORDER BY Period ASC, wc.Category_Name ASC";

    $res = $conn->query($sql);
    if(!$res) sendResponse(false, [], $conn->error);

    $data = [];
    while($row = $res->fetch_assoc()){
        // Ensure numeric value
        $row['Total_KG'] = floatval($row['Total_KG']);
        $data[] = $row;
    }

    if(empty($data)) sendResponse(true, [], 'No data for selected period/area');

    sendResponse(true, $data);
}

sendResponse(false, [], 'Invalid type parameter');
?>
