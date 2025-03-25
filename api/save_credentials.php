<?php
header('Content-Type: application/json');
require_once "../config/database.php";

try {
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Invalid data format');
    }

    // Validate email and password
    $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    $password = $data['password'];

    if (!$email || !$password) {
        throw new Exception('Invalid email or password');
    }

    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    // Check if email already exists
    $checkQuery = "SELECT id FROM tbl_users WHERE email = :email";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':email', $email);
    $checkStmt->execute();

    if ($checkStmt->rowCount() > 0) {
        // User already exists, return success without updating
        $userId = $checkStmt->fetch(PDO::FETCH_ASSOC)['id'];
        echo json_encode([
            'success' => true,
            'message' => 'User already registered',
            'data' => [
                'userId' => $userId,
                'email' => $email,
                'savedAt' => date('Y-m-d H:i:s')
            ]
        ]);
        exit;
    }

    // Only proceed with insertion for new users
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $query = "INSERT INTO tbl_users (email, password) VALUES (:email, :password)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashedPassword);
    
    if ($stmt->execute()) {
        $userId = $db->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Credentials saved successfully',
            'data' => [
                'userId' => $userId,
                'email' => $email,
                'savedAt' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        throw new Exception('Failed to save credentials');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}