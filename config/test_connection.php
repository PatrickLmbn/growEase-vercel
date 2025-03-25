<?php
require_once "database.php";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if($db) {
        echo "✅ Database connection successful!";
    }
} catch(Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage();
} 