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

    $query = "DELETE FROM tbl_plants WHERE moisture_pin = :pin";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':pin', $pin);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Plant deleted successfully'
            ]);
        } else {
            throw new Exception('No plant found with the specified pin');
        }
    } else {
        throw new Exception('Failed to delete plant');
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