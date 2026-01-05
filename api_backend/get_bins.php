<?php
include '../includes_backend/db_connect.php';
include '../includes_backend/functions.php';

// Fetch ALL bins with latest status + enforce Empty => no action required
$sql = "
SELECT 
    b.Bin_ID,
    b.Location,
    b.Variety AS Category,

    -- Fix Status: if NULL or '' → 'empty'
    CASE 
        WHEN bs.Status IS NULL OR bs.Status = '' THEN 'empty'
        ELSE LOWER(bs.Status)
    END AS Status,

    -- Fix Action:
    -- 1. If status is 'empty' → ALWAYS 'no action required'
    -- 2. If action NULL, '' or 'no action' → 'no action required'
    CASE
        WHEN bs.Status IS NULL OR bs.Status = '' OR LOWER(bs.Status) = 'empty'
            THEN 'no action required'
        WHEN bs.Action IS NULL 
            OR bs.Action = '' 
            OR LOWER(bs.Action) = 'no action'
            THEN 'no action required'
        ELSE bs.Action
    END AS Action

FROM Bin b

LEFT JOIN (
    SELECT 
        bs1.Bin_ID, 
        bs1.Status, 
        bs1.Action
    FROM Bin_Status bs1
    INNER JOIN (
        SELECT Bin_ID, MAX(Status_ID) AS max_status
        FROM Bin_Status
        GROUP BY Bin_ID
    ) latest
    ON bs1.Bin_ID = latest.Bin_ID AND bs1.Status_ID = latest.max_status
) bs ON b.Bin_ID = bs.Bin_ID

ORDER BY b.Bin_ID ASC;
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $bins = [];
    while ($row = $result->fetch_assoc()) {

        // Final safety check (just in case)
        if (strtolower($row['Status']) === 'empty') {
            $row['Action'] = 'no action required';
        }

        $bins[] = $row;
    }

    jsonResponse(true, "All bins with latest status fetched successfully.", $bins);
} else {
    jsonResponse(false, "No bins found.", []);
}
?>


