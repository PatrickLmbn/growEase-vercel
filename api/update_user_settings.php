<?php
session_start();
header('Content-Type: application/json');
require_once "../config/database.php";

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Invalid data format');
    }

    // Create database connection
    $database = new Database();
    $db = $database->getConnection();

    // Start transaction
    $db->beginTransaction();

    try {
        // Update username and gender if provided
        if (!empty($data['username']) || !empty($data['gender'])) {
            $updateQuery = "UPDATE tbl_users SET ";
            $updates = [];
            $params = [];

            if (!empty($data['username'])) {
                $updates[] = "username = :username";
                $params[':username'] = $data['username'];
            }

            if (!empty($data['gender'])) {
                $updates[] = "gender = :gender";
                $params[':gender'] = $data['gender'];
            }

            $updateQuery .= implode(', ', $updates);
            $updateQuery .= " WHERE id = :user_id";
            $params[':user_id'] = $_SESSION['user_id'];

            $stmt = $db->prepare($updateQuery);
            $stmt->execute($params);
        }

        // Update password if old, new, and confirm passwords are provided
        if (!empty($data['oldPassword']) && !empty($data['newPassword']) && !empty($data['confirmPassword'])) {
            // Verify old password
            $stmt = $db->prepare("SELECT password FROM tbl_users WHERE id = :user_id");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!password_verify($data['oldPassword'], $user['password'])) {
                throw new Exception('Current password is incorrect');
            }

            // Check if new password and confirm password match
            if ($data['newPassword'] !== $data['confirmPassword']) {
                throw new Exception('New password and confirm password do not match');
            }

            // Update with new password
            $hashedPassword = password_hash($data['newPassword'], PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE tbl_users SET password = :password WHERE id = :user_id");
            $stmt->execute([
                ':password' => $hashedPassword,
                ':user_id' => $_SESSION['user_id']
            ]);
        }

        // Commit transaction
        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 