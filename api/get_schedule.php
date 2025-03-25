<?php
header('Content-Type: application/json');
require_once "../config/database.php";

try {
    // Get moisture_pin from query parameter
    $pin = isset($_GET['pin']) ? $_GET['pin'] : null;

    if (!$pin) {
        throw new Exception('No pin provided');
    }

    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT schedule, name FROM tbl_plants WHERE moisture_pin = :pin";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':pin', $pin);

    if ($stmt->execute()) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'schedule' => $result['schedule'],
                'name' => $result['name']
            ]);
        } else {
            throw new Exception('No plant found with the specified pin');
        }
    } else {
        throw new Exception('Failed to fetch schedule');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($db)) {
        $db = null;
    }
}
?>