<?php
include '../includes_backend/db_connect.php';
include '../includes_backend/functions.php';
//include '../includes_backend/auth.php'; // ensures only logged-in users can access





    // Collect form data
    $location = sanitize($_POST['location']);
    $variety = sanitize($_POST['variety']); // e.g. "Plastic", "Organic", etc.

    // Insert new bin into the database
    $sql = $conn->prepare("INSERT INTO Bin (Location, Variety) VALUES (?, ?)");
    $sql->bind_param("ss", $location, $variety);

    if ($sql->execute()) {
        // After bin creation, create a default empty status record
        $bin_id = $conn->insert_id;

        $status_sql = $conn->prepare("INSERT INTO Bin_Status (Bin_ID, Status, Action) VALUES (?, 'empty', 'No action')");
        $status_sql->bind_param("i", $bin_id);
        $status_sql->execute();

        jsonResponse(true, "New bin added successfully.");
    } else {
        jsonResponse(false, "Error adding bin: " . $conn->error);
    }

?>
