<?php
include '../includes_backend/db_connect.php';
include '../includes_backend/functions.php';
include '../includes_backend/auth.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

switch ($action) {
    case 'get':
        getNotifications($conn);
        break;
    case 'read':
        markNotificationAsRead($conn);
        break;
    default:
        jsonResponse(false, "Invalid action.");
        break;
}

/**
 * 📨 Get bins whose latest status is 'full' 
 *     and action = 'no action has been taken yet'
 */
function getNotifications($conn)
{
    try {
        // Query to get bins with latest status = 'full' and specific action
        $sql = "
            SELECT 
                b.Bin_ID,
                b.Location,
                b.Variety,
                bs.Status,
                bs.Action
            FROM Bin_Status bs
            INNER JOIN (
                SELECT Bin_ID, MAX(Status_ID) AS LatestStatusID
                FROM Bin_Status
                GROUP BY Bin_ID
            ) latest ON bs.Status_ID = latest.LatestStatusID
            INNER JOIN Bin b ON b.Bin_ID = bs.Bin_ID
            WHERE bs.Status = 'full'
              AND bs.Action = 'No action has been taken yet.'
            ORDER BY b.Bin_ID ASC
        ";

        $result = $conn->query($sql);

        if (!$result) {
            throw new Exception("Database query failed: " . $conn->error);
        }

        $bins = [];
        while ($row = $result->fetch_assoc()) {
            $bins[] = [
                'Bin_ID' => $row['Bin_ID'],
                'Location' => $row['Location'],
                'Variety' => $row['Variety'],
                'Status' => $row['Status'],
                'Action' => $row['Action']
            ];
        }

        jsonResponse(true, "Bins fetched successfully.", $bins);
    } catch (Exception $e) {
        jsonResponse(false, "Error: " . $e->getMessage());
    }
}
?>
