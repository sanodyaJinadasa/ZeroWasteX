<?php
header("Content-Type: application/json");
include "../includes_backend/db_connect.php";
include '../includes_backend/functions.php';

$action = $_POST['action'] ?? '';

// If NO action → load dropdown data
if ($action === '') {

    // Load user roles
    $roles = [];
    $result = $conn->query("SELECT DISTINCT role FROM user");
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row['role'];
    }

    // Load locations
    $locations = [];
    $result = $conn->query("SELECT location_name FROM locations");
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row['location_name'];
    }

    // Load bin categories
    $categories = [];
    $result = $conn->query("SELECT DISTINCT Variety FROM bin");
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['Variety'];
    }

    echo json_encode([
        "success" => true,
        "users" => $roles,
        "locations" => $locations,
        "categories" => $categories
    ]);
    exit;
}

try {

    // ============================
    // DELETE USER
    // ============================
    if ($action === "delete_user") {
       $role = $_POST["role"] ?? "";
       $user_id = $_POST["user_ID"] ?? "";

       if ($role === "" || $user_id === "") {
         echo json_encode([
            "success" => false,
            "message" => "Role and user_ID are required"
        ]);
        exit;
    }

    // Prepare the DELETE statement
    $stmt = $conn->prepare("DELETE FROM user WHERE role = ? AND user_ID = ?");
    $stmt->bind_param("si", $role, $user_id); // assuming user_ID is an integer

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "User removed successfully"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to remove user"
        ]);
    }
    exit;
}


    // ============================
    // DELETE LOCATION
    // ============================
    if ($action === "delete_location") {
        $location = $_POST["location"] ?? "";
        if ($location == "") {
            echo json_encode(["success" => false, "message" => "No location provided"]);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM locations WHERE location_name = ?");
        $stmt->bind_param("s", $location);

        echo $stmt->execute()
            ? json_encode(["success" => true, "message" => "Location removed"])
            : json_encode(["success" => false, "message" => "Failed to remove location"]);
        exit;
    }

    // ============================
    // DELETE BIN
    // ============================
    if ($action === "delete_bin") {
        $category = $_POST["category"] ?? "";
        if ($category == "") {
            echo json_encode(["success" => false, "message" => "No bin category provided"]);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM bin WHERE Variety = ?");
        $stmt->bind_param("s", $category);

        echo $stmt->execute()
            ? json_encode(["success" => true, "message" => "Bin(s) removed"])
            : json_encode(["success" => false, "message" => "Failed to remove bin"]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Invalid action"]);
    exit;

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    exit;
}
?>
