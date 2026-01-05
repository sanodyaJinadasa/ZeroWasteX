<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include '../includes_backend/db_connect.php';
include '../includes_backend/functions.php';

$response = [
    "status" => false,
    "data" => [],
    "error" => ""
];

// --------------------------------------------------
// Validate "report" parameter
// --------------------------------------------------
$report = $_GET['report'] ?? '';

if ($report !== "areaWiseWaste") {
    $response["error"] = "Invalid report type.";
    echo json_encode($response);
    exit;
}

// --------------------------------------------------
// Read period type
// --------------------------------------------------
$period = $_GET["period"] ?? "";
$where = "";
$params = [];
$types = "";

// --------------------------------------------------
// BUILD DATE FILTER BASED ON PERIOD
// --------------------------------------------------
switch ($period) {

    case "day":
        if (!isset($_GET["date"])) {
            $response["error"] = "Missing date.";
            echo json_encode($response);
            exit;
        }
        $where = "DATE(UWR.Updated_At) = ?";
        $params[] = $_GET["date"];
        $types .= "s";
        break;

    case "week":
        if (!isset($_GET["year"], $_GET["month"], $_GET["week"])) {
            $response["error"] = "Missing year, month, or week.";
            echo json_encode($response);
            exit;
        }

        $year = intval($_GET["year"]);
        $month = intval($_GET["month"]);
        $week = intval($_GET["week"]);

        // Get all week numbers in given month
        $start = new DateTime("$year-$month-01");
        $end = new DateTime("$year-$month-" . $start->format("t"));

        $weekDates = [];
        while ($start <= $end) {
            if ($start->format("W") == $week) {
                $weekDates[] = $start->format("Y-m-d");
            }
            $start->modify("+1 day");
        }

        if (empty($weekDates)) {
            $response["error"] = "Invalid week selection.";
            echo json_encode($response);
            exit;
        }

        $placeholders = implode(",", array_fill(0, count($weekDates), "?"));
        $where = "DATE(UWR.Updated_At) IN ($placeholders)";
        $params = array_merge($params, $weekDates);
        $types .= str_repeat("s", count($weekDates));
        break;

    case "month":
        if (!isset($_GET["year"], $_GET["month"])) {
            $response["error"] = "Missing year or month.";
            echo json_encode($response);
            exit;
        }
        $where = "YEAR(UWR.Updated_At) = ? AND MONTH(UWR.Updated_At) = ?";
        $params[] = $_GET["year"];
        $params[] = $_GET["month"];
        $types .= "ii";
        break;

    case "year":
        if (!isset($_GET["year"])) {
            $response["error"] = "Missing year.";
            echo json_encode($response);
            exit;
        }
        $where = "YEAR(UWR.Updated_At) = ?";
        $params[] = $_GET["year"];
        $types .= "i";
        break;

    default:
        $response["error"] = "Invalid period.";
        echo json_encode($response);
        exit;
}

// --------------------------------------------------
// SQL QUERY — GROUP BY BIN LOCATION
// --------------------------------------------------
$sql = "
    SELECT 
        B.Location AS Area,
        SUM(UWR.Amount_KG) AS Total_Amount_KG
    FROM User_Waste_Record UWR
    LEFT JOIN Bin B ON UWR.Bin_ID = B.Bin_ID
    WHERE $where
    GROUP BY B.Location
    ORDER BY Total_Amount_KG DESC
";

// --------------------------------------------------
// Execute Prepared Statement
// --------------------------------------------------
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();

$result = $stmt->get_result();
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$response["status"] = true;
$response["data"] = $data;

echo json_encode($response);
exit;
?>
