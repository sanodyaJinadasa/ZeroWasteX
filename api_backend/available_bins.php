<?php
header('Content-Type: application/json');
include '../includes_backend/db_connect.php';

// Get category from POST or GET
$category = '';
if (!empty($_POST['category'])) {
    $category = trim($_POST['category']);
} elseif (!empty($_GET['category'])) {
    $category = trim($_GET['category']);
}

if (empty($category)) {
    echo json_encode([
        'success' => false,
        'message' => 'No category selected.',
        'data' => []
    ]);
    exit;
}

$category_clean = strtolower(trim($category));

try {

    if ($category_clean === 'all') {

        // All bins
        $sql = "
            SELECT 
                b.Bin_ID,
                b.Location,
                TRIM(b.Variety) AS Variety,
                COALESCE(bs.Status, 'empty') AS Status
            FROM Bin b
            LEFT JOIN (
                SELECT bs1.Bin_ID, bs1.Status
                FROM Bin_Status bs1
                INNER JOIN (
                    SELECT Bin_ID, MAX(Status_ID) AS max_status
                    FROM Bin_Status
                    GROUP BY Bin_ID
                ) latest 
                ON bs1.Bin_ID = latest.Bin_ID AND bs1.Status_ID = latest.max_status
            ) bs 
            ON b.Bin_ID = bs.Bin_ID
            WHERE COALESCE(bs.Status, 'empty') IN ('empty', 'half')
        ";

        $stmt = $conn->prepare($sql);

    } else {

        // Specific category
        $sql = "
            SELECT 
                b.Bin_ID,
                b.Location,
                TRIM(b.Variety) AS Variety,
                COALESCE(bs.Status, 'empty') AS Status
            FROM Bin b
            LEFT JOIN (
                SELECT bs1.Bin_ID, bs1.Status
                FROM Bin_Status bs1
                INNER JOIN (
                    SELECT Bin_ID, MAX(Status_ID) AS max_status
                    FROM Bin_Status
                    GROUP BY Bin_ID
                ) latest 
                ON bs1.Bin_ID = latest.Bin_ID AND bs1.Status_ID = latest.max_status
            ) bs 
            ON b.Bin_ID = bs.Bin_ID
            WHERE LOWER(
                REPLACE(REPLACE(REPLACE(TRIM(b.Variety), '\n', ''), '\r', ''), '\t', '')
            ) 
            =
            LOWER(
                REPLACE(REPLACE(REPLACE(TRIM(?), '\n', ''), '\r', ''), '\t', '')
            )
            AND COALESCE(bs.Status, 'empty') IN ('empty', 'half')
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $category);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {

        if (is_null($row['Status']) || $row['Status'] === "") {
            $row['Status'] = 'empty';
        }

        $data[] = $row;
    }

    if (!empty($data)) {
        echo json_encode(['success' => true, 'data' => $data, 'message' => '']);
    } else {
        echo json_encode(['success' => false, 'data' => [], 'message' => 'No bins found or all bins full.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'data' => [], 'message' => $e->getMessage()]);
}

?>


