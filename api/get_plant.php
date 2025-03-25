<?php
header('Content-Type: application/json');
require_once "../config/database.php";

try {
    $pin = isset($_GET['pin']) ? $_GET['pin'] : null;
    
    if (!$pin) {
        throw new Exception('No pin provided');
    }

    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT * FROM tbl_plants WHERE moisture_pin = :pin";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':pin', $pin);
    $stmt->execute();
    
    $plant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($plant) {
        echo json_encode([
            'success' => true,
            'plant' => $plant
        ]);
    } else {
        throw new Exception('Plant not found');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 