<?php
require_once("../middleware/auth.php");

header("Content-Type: application/json");

try {
    // Authenticate the user (requires a valid JWT token stored in HttpOnly cookie)
    $userData = verifyToken();

    $stmt = $conn->query("
        SELECT day, meal_type, items 
        FROM mess_menu 
        ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), meal_type
    ");
    
    $routine = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "success",
        "data" => $routine
    ]);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
