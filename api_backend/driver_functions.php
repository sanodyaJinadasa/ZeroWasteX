<?php
error_reporting(0); // Hide warnings
header('Content-Type: application/json');
session_start();
include '../includes_backend/db_connect.php';
include '../includes_backend/functions_bin_stats.php';

// Check session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Call functions
$totalBins = countTodayCollectedBins($conn, $user_id);
$pendingBins = countBinsWithoutWasteRecord($conn, $user_id);
$totalWaste = sumTodayWasteAmount($conn, $user_id);
$priorityBinDetails = priorityBins($conn, $user_id);
// Return as JSON
header('Content-Type: application/json');
echo json_encode([
    "success" => true,
    "bins_without_waste_record_today" => $pendingBins,
    "bins_collected_today" => $totalBins,
    "total_waste_kg_today" => $totalWaste,
    "priority_bins" => $priorityBinDetails
]);
exit;

// ---------------- Functions ----------------
function countBinsWithoutWasteRecord($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT Bin_IDs 
        FROM Collection 
        WHERE User_ID = ? 
          AND Collection_Date = CURDATE()
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $todayCollectionBins = [];
    while ($row = $result->fetch_assoc()) {
        $ids = array_map('trim', explode(',', $row['Bin_IDs']));
        $todayCollectionBins = array_merge($todayCollectionBins, $ids);
    }
    $todayCollectionBins = array_unique(array_filter($todayCollectionBins));
    if (empty($todayCollectionBins)) return 0;

    $stmt2 = $conn->prepare("
        SELECT DISTINCT Bin_ID 
        FROM User_Waste_Record 
        WHERE User_ID = ? 
          AND DATE(Updated_At) = CURDATE()
          AND Bin_ID IS NOT NULL
    ");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    $binsWithWaste = [];
    while ($row2 = $result2->fetch_assoc()) {
        $binsWithWaste[] = $row2['Bin_ID'];
    }

    $binsWithoutWaste = array_diff($todayCollectionBins, $binsWithWaste);
    return count($binsWithoutWaste);
}

function countTodayCollectedBins($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT Bin_IDs 
        FROM Collection 
        WHERE User_ID = ? 
          AND Collection_Date = CURDATE()
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $bins = [];
    while ($row = $result->fetch_assoc()) {
        $ids = array_map('trim', explode(',', $row['Bin_IDs']));
        $bins = array_merge($bins, $ids);
    }
    $bins = array_unique(array_filter($bins));

    return count($bins);
}

function sumTodayWasteAmount($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(Amount_KG), 0) AS total_amount
        FROM User_Waste_Record
        WHERE User_ID = ? 
          AND DATE(Updated_At) = CURDATE()
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return (float)$result['total_amount'];
}
function priorityBins($conn, $user_id) {
    // Step 1: Get all bins assigned to the user for today
    $stmt = $conn->prepare("
        SELECT Bin_IDs 
        FROM Collection 
        WHERE User_ID = ? 
          AND Collection_Date = CURDATE()
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $todayCollectionBins = [];
    while ($row = $result->fetch_assoc()) {
        $ids = array_map('trim', explode(',', $row['Bin_IDs']));
        $todayCollectionBins = array_merge($todayCollectionBins, $ids);
    }
    $todayCollectionBins = array_unique(array_filter($todayCollectionBins));
    if (empty($todayCollectionBins)) return []; // no bins assigned today

    // Step 2: Get bins that already have waste records today
    $stmt2 = $conn->prepare("
        SELECT DISTINCT Bin_ID 
        FROM User_Waste_Record 
        WHERE User_ID = ? 
          AND DATE(Updated_At) = CURDATE()
          AND Bin_ID IS NOT NULL
    ");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    $binsWithWaste = [];
    while ($row2 = $result2->fetch_assoc()) {
        $binsWithWaste[] = $row2['Bin_ID'];
    }

    // Step 3: Calculate bins without waste record
    $binsWithoutWaste = array_diff($todayCollectionBins, $binsWithWaste);
    if (empty($binsWithoutWaste)) return [];

    // Step 4: Fetch details from Bin and latest status from Bin_Status
    $bin_ids_placeholders = implode(',', array_fill(0, count($binsWithoutWaste), '?'));
    $types = str_repeat('i', count($binsWithoutWaste));

    $stmt3 = $conn->prepare("
        SELECT b.Bin_ID, b.Location, b.Variety, bs.Status
        FROM Bin b
        LEFT JOIN Bin_Status bs ON bs.Bin_ID = b.Bin_ID
        INNER JOIN (
            SELECT Bin_ID, MAX(Status_ID) AS LatestStatusID
            FROM Bin_Status
            WHERE Bin_ID IN ($bin_ids_placeholders)
            GROUP BY Bin_ID
        ) latest ON bs.Status_ID = latest.LatestStatusID
        WHERE b.Bin_ID IN ($bin_ids_placeholders)
    ");

    // Bind params twice (for latest subquery and WHERE)
    $params = array_merge($binsWithoutWaste, $binsWithoutWaste);
    $stmt3->bind_param($types . $types, ...$params);

    $stmt3->execute();
    $result3 = $stmt3->get_result();

    $binDetails = [];
    while ($row3 = $result3->fetch_assoc()) {
        $binDetails[] = [
            'Bin_ID' => $row3['Bin_ID'],
            'Location' => $row3['Location'],
            'Variety' => $row3['Variety'],
            'Status' => $row3['Status']
        ];
    }

    return $binDetails;
}

?>
