<?php
header('Content-Type: application/json');
require_once "../config/database.php";

try {
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid data format']);
        exit;
    }

    // Extract data
    $name = $data['name'] ?? '';
    $type = $data['type'] ?? 'Unspecified';
    $moisture_pin = $data['moisture_pin'] ?? '';
    $created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
    $updated_at = $data['updated_at'] ?? date('Y-m-d H:i:s');

    // Validate data
    if (empty($name) || empty($moisture_pin)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    // Prepare insert query
    $query = "INSERT INTO tbl_plants (name, type, moisture_pin, created_at, updated_at) 
              VALUES (:name, :type, :moisture_pin, :created_at, :updated_at)";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':moisture_pin', $moisture_pin);
    $stmt->bindParam(':created_at', $created_at);
    $stmt->bindParam(':updated_at', $updated_at);
    
    // Execute query
    if ($stmt->execute()) {
        // After successful insert, fetch and return the complete plant data
        $plant = [
            "id" => $db->lastInsertId(),
            "name" => $name,
            "type" => $type,
            "moisture_pin" => $moisture_pin,
            "last_watered" => null,
            "created_at" => $created_at,
            "updated_at" => $updated_at
        ];

        echo json_encode([
            "success" => true,
            "message" => "Plant added successfully",
            "plant" => $plant
        ]);
    } else {
        throw new Exception('Failed to add plant');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 