<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

header('Content-Type: application/json');
session_start();

include '../includes_backend/db_connect.php';
include '../includes_backend/functions.php';

// Check session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];

/**
 * Get bins assigned to the logged-in user today but not yet recorded as collected
 */
function getPriorityBins($conn, $user_id) {
    // Fetch today's collection bins
    $stmt = $conn->prepare("
        SELECT Bin_IDs 
        FROM Collection 
        WHERE User_ID = ? AND Collection_Date = CURDATE()
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $todayCollectionBins = [];
    while ($row = $result->fetch_assoc()) {
        $ids = array_map('trim', explode(',', $row['Bin_IDs']));
        $todayCollectionBins = array_merge($todayCollectionBins, $ids);
    }

    // Unique + clean
    $todayCollectionBins = array_unique(array_filter($todayCollectionBins));
    if (empty($todayCollectionBins)) return [];

    // Exclude bins already recorded in User_Waste_Record
    $stmt2 = $conn->prepare("
        SELECT DISTINCT Bin_ID 
        FROM User_Waste_Record 
        WHERE User_ID = ? AND DATE(Updated_At) = CURDATE() AND Bin_ID IS NOT NULL
    ");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    $binsWithWaste = [];
    while ($row2 = $result2->fetch_assoc()) {
        $binsWithWaste[] = $row2['Bin_ID'];
    }

    return array_values(array_diff($todayCollectionBins, $binsWithWaste));
}

/**
 * Generate Google Maps route URL
 */
function generateRoute($conn, $user_id) {
    // Step 1: Get priority bins
    $bins = getPriorityBins($conn, $user_id);
    if (empty($bins)) {
        echo json_encode([
            "success" => false,
            "message" => "No bins to collect today."
        ]);
        exit;
    }

    // Step 2: Build placeholder list for prepared statement
    $placeholders = implode(',', array_fill(0, count($bins), '?'));
    $types = str_repeat('i', count($bins));

    // Step 3: Fetch only valid bin coordinates from backend
    $sql = "
        SELECT 
            Bin.Bin_ID,
            Bin.Location,
            L.Latitude,
            L.Longitude
        FROM Bin
        INNER JOIN Locations L 
            ON Bin.Location = L.Location_Name
        WHERE Bin.Bin_ID IN ($placeholders)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$bins);
    $stmt->execute();
    $result = $stmt->get_result();

    $waypoints = [];

    while ($row = $result->fetch_assoc()) {
        if (isset($row['Latitude'], $row['Longitude']) && $row['Latitude'] !== "" && $row['Longitude'] !== "") {
            $coordinate = $row['Latitude'] . "," . $row['Longitude'];
            $waypoints[] = $coordinate;
        }
    }

    // Make unique
    $waypoints = array_unique($waypoints);

    if (empty($waypoints)) {
        echo json_encode([
            "success" => false,
            "message" => "No valid coordinates found for filtered locations."
        ]);
        exit;
    }

    // Depot coordinates (start/end)
    $depot_lat = 6.725707175664425;
    $depot_lng = 80.7858548691336;
    $depot = urlencode("$depot_lat,$depot_lng");

    // Step 4: Build Google waypoint string
    $waypoints_str = implode('|', array_map('urlencode', $waypoints));

    // Step 5: Build final Google Maps URL
    $finalUrl = "https://www.google.com/maps/dir/?api=1"
              . "&origin={$depot}"
              . "&destination={$depot}"
              . "&waypoints=optimize:true|{$waypoints_str}";

    echo json_encode([
        "success" => true,
        "route_url" => $finalUrl,
        "waypoints" => $waypoints
    ]);
    exit;
}
generateRoute($conn, $user_id);
?>

