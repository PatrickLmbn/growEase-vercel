<?php
header('Content-Type: application/json');
require_once "../config/database.php";

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    // Get plant info from database
    $query = "SELECT id, name, moisture_pin, last_watered 
              FROM tbl_plants 
              WHERE moisture_pin IS NOT NULL";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $plants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'plants' => $plants
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 