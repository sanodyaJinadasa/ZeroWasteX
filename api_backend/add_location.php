<?php
header('Content-Type: application/json');
include '../includes_backend/db_connect.php';
include '../includes_backend/functions.php';

// Get POST data
$location_name = isset($_POST['name']) ? trim($_POST['name']) : '';
$latitude = isset($_POST['latitude']) ? trim($_POST['latitude']) : '';
$longitude = isset($_POST['longitude']) ? trim($_POST['longitude']) : '';

// Validation
if (empty($location_name) || empty($latitude) || empty($longitude)) {
    echo json_encode(["success" => false, "message" => "All fields are required."]);
    exit;
}

// Check if location already exists
$stmt = $conn->prepare("SELECT Location_ID FROM Locations WHERE Location_Name = ?");
$stmt->bind_param("s", $location_name);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Location already exists."]);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Insert new location
$stmt = $conn->prepare("INSERT INTO Locations (Location_Name, Latitude, Longitude) VALUES (?, ?, ?)");
$stmt->bind_param("sdd", $location_name, $latitude, $longitude);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Location added successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to add location: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
