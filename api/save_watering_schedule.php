<?php
header('Content-Type: application/json');
require_once "../config/database.php";

try {
    // Get and validate JSON data
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if (!$data || !isset($data['moisture_pin']) || !isset($data['schedule'])) {
        throw new Exception('Invalid data format. Expected moisture_pin and schedule.');
    }

    $moisture_pin = $data['moisture_pin'];
    $schedule = $data['schedule'];

    $database = new Database();
    $db = $database->getConnection();

    $query = "UPDATE tbl_plants SET schedule = :schedule WHERE moisture_pin = :pin";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':schedule', $schedule);
    $stmt->bindParam(':pin', $moisture_pin);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Watering schedule updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update schedule');
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