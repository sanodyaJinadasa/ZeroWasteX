<?php
//report for collection visits
include '../includes_backend/db_connect.php';
include '../includes_backend/functions.php';
include '../includes/auth.php';
//session_start();

      // Only admins/officers can view reports
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'officer') {
    jsonResponse(false, "Access denied. Only admins/officers can view reports.");
    exit;
}

$sql = "
    SELECT 
        c.Collection_ID,
        u.Name AS Driver_Name,
        c.Date,
        c.Time,
        c.Bin_IDs
    FROM Collection c
    INNER JOIN User u ON c.User_ID = u.User_ID
    ORDER BY c.Date DESC, c.Time DESC
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $collections = [];
    while ($row = $result->fetch_assoc()) {
        $collections[] = $row;
    }
    jsonResponse(true, "Collection visits report generated.", $collections);
} else {
    jsonResponse(false, "No collection visits found.");
}


// report for area wise waste collection including total,separate by days

     //  Query: Join Collection + Bin by matching Bin_IDs stored in comma-separated list
     // Group by Date + Location and count number of collections per area per day
$sql = "
    SELECT 
        b.Location,
        c.Date,
        COUNT(DISTINCT c.Collection_ID) AS Collection_Count
    FROM Collection c
    INNER JOIN Bin b 
        ON FIND_IN_SET(b.Bin_ID, c.Bin_IDs)
    GROUP BY b.Location, c.Date
    ORDER BY c.Date DESC, b.Location ASC
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $report = [];
    while ($row = $result->fetch_assoc()) {
        $report[] = $row;
    }

    jsonResponse(true, "Area-wise waste collection report generated successfully.", $report);
} else {
    jsonResponse(false, "No collection data found for area-wise report.");
}



//report for catogory wise waste collected amounts separate by days and totals


$sql = "
    SELECT 
        wcat.Category_Name,
        DATE(uwr.Updated_At) AS Date,
        SUM(uwr.Amount_KG) AS Total_Collected_KG
    FROM User_Waste_Record uwr
    INNER JOIN Waste_Category wcat ON uwr.Category_ID = wcat.Category_ID
    GROUP BY wcat.Category_Name, DATE(uwr.Updated_At)
    ORDER BY Date DESC, wcat.Category_Name
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $dailyData = [];
    $totalByCategory = [];

    while ($row = $result->fetch_assoc()) {
        $dailyData[] = $row;

        // Keep total per category
        $cat = $row['Category_Name'];
        $totalByCategory[$cat] = ($totalByCategory[$cat] ?? 0) + $row['Total_Collected_KG'];
    }

    jsonResponse(true, "Category-wise waste report generated.", [
        "daily" => $dailyData,
        "totals" => $totalByCategory
    ]);
} else {
    jsonResponse(false, "No waste records found.");
}


?>