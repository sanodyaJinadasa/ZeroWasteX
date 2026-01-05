<?php
include '../includes_backend/db_connect.php';
include '../includes_backend/functions.php';

header("Content-Type: application/json");

$sql = "SELECT Location_ID, Location_Name FROM Locations ORDER BY Location_Name ASC";
$result = $conn->query($sql);

$locations = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
}

echo json_encode([
    "success" => true,
    "locations" => $locations
]);
?>
