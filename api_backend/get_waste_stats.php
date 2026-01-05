<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include '../includes_backend/db_connect.php';
include '../includes_backend/functions.php';

// Validate report type
$report = $_GET["report"] ?? "";
if ($report !== "totalWaste") {
    echo json_encode(["status" => "error", "message" => "Invalid report type"]);
    exit();
}

$period = $_GET["period"] ?? "";

$response = [];
$query = "";
$params = [];
$types = "";

/* -------------------------------------------------------
   DAILY REPORT
------------------------------------------------------- */
if ($period === "day") {
    if (!isset($_GET["date"])) {
        echo json_encode(["status" => "error", "message" => "Missing date"]);
        exit();
    }

    $date = $_GET["date"];

    $query = "
        SELECT wc.Category_Name, 
               COALESCE(SUM(uw.Amount_KG),0) AS Total_Collected_KG
        FROM Waste_Category wc
        LEFT JOIN User_Waste_Record uw 
               ON wc.Category_ID = uw.Category_ID
               AND DATE(uw.Updated_At) = ?
        GROUP BY wc.Category_ID
        ORDER BY wc.Category_ID
    ";

    $params = [$date];
    $types = "s";
}

/* -------------------------------------------------------
   WEEKLY REPORT
------------------------------------------------------- */
else if ($period === "week") {

    $year = $_GET["year"] ?? "";
    $month = $_GET["month"] ?? "";
    $week = $_GET["week"] ?? "";

    if (!$year || !$month || !$week) {
        echo json_encode(["status" => "error", "message" => "Missing weekly params"]);
        exit();
    }

    // Get first day of month
    $startMonth = date("Y-m-01", strtotime("$year-$month-01"));

    // Determine all dates in selected week
    $datesInWeek = [];
    $totalDays = date("t", strtotime($startMonth));

    for ($d = 1; $d <= $totalDays; $d++) {
        $current = date("Y-m-d", strtotime("$year-$month-$d"));
        $weekNum = ceil((date("z", strtotime($current)) + 1) / 7);
        if ($weekNum == $week) {
            $datesInWeek[] = $current;
        }
    }

    if (empty($datesInWeek)) {
        echo json_encode(["status" => "success", "data" => []]);
        exit();
    }

    // Create placeholders (?, ?, ?, ...) for IN()
    $placeholders = implode(",", array_fill(0, count($datesInWeek), "?"));
    $types = str_repeat("s", count($datesInWeek));

    $query = "
        SELECT wc.Category_Name, 
               COALESCE(SUM(uw.Amount_KG),0) AS Total_Collected_KG
        FROM Waste_Category wc
        LEFT JOIN User_Waste_Record uw 
               ON wc.Category_ID = uw.Category_ID
               AND DATE(uw.Updated_At) IN ($placeholders)
        GROUP BY wc.Category_ID
        ORDER BY wc.Category_ID
    ";

    $params = $datesInWeek;
}

/* -------------------------------------------------------
   MONTHLY REPORT
------------------------------------------------------- */
else if ($period === "month") {
    $year = $_GET["year"] ?? "";
    $month = $_GET["month"] ?? "";

    if (!$year || !$month) {
        echo json_encode(["status" => "error", "message" => "Missing month params"]);
        exit();
    }

    $query = "
        SELECT wc.Category_Name, 
               COALESCE(SUM(uw.Amount_KG),0) AS Total_Collected_KG
        FROM Waste_Category wc
        LEFT JOIN User_Waste_Record uw 
               ON wc.Category_ID = uw.Category_ID
               AND YEAR(uw.Updated_At) = ?
               AND MONTH(uw.Updated_At) = ?
        GROUP BY wc.Category_ID
        ORDER BY wc.Category_ID
    ";

    $params = [$year, $month];
    $types = "ss";
}

/* -------------------------------------------------------
   YEARLY REPORT
------------------------------------------------------- */
else if ($period === "year") {

    $year = $_GET["year"] ?? "";

    if (!$year) {
        echo json_encode(["status" => "error", "message" => "Missing year"]);
        exit();
    }

    $query = "
        SELECT wc.Category_Name, 
               COALESCE(SUM(uw.Amount_KG),0) AS Total_Collected_KG
        FROM Waste_Category wc
        LEFT JOIN User_Waste_Record uw 
               ON wc.Category_ID = uw.Category_ID
               AND YEAR(uw.Updated_At) = ?
        GROUP BY wc.Category_ID
        ORDER BY wc.Category_ID
    ";

    $params = [$year];
    $types = "s";
}

/* -------------------------------------------------------
   RUN QUERY
------------------------------------------------------- */
$stmt = $conn->prepare($query);

if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        "Category_Name" => $row["Category_Name"],
        "Total_Collected_KG" => floatval($row["Total_Collected_KG"])
    ];
}

// Final Response
echo json_encode([
    "status" => "success",
    "data" => $data
]);

$conn->close();
?>

