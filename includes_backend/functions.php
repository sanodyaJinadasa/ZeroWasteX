

<?php
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function jsonResponse($success, $message, $data = null) {
    
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);
}
?>
