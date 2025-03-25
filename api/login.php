<?php
session_start();
header('Content-Type: application/json');
require_once "../config/database.php";

try {
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Invalid data format');
    }

    // Validate input
    $identifier = $data['identifier']; // Can be email or username
    $password = $data['password'];

    if (!$identifier || !$password) {
        throw new Exception('Invalid identifier or password');
    }

    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    // Get user from database using email or username
    $query = "SELECT id, email, username, password FROM tbl_users WHERE email = :identifier OR username = :identifier";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':identifier', $identifier);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];

            echo json_encode([
                'success' => true,
                'message' => 'Login successful'
            ]);
        } else {
            throw new Exception('Invalid identifier or password');
        }
    } else {
        throw new Exception('Invalid identifier or password');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 