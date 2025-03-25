<?php
// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration (if needed)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'your_database');

// Site constants
define('SITE_NAME', 'My Website');
define('SITE_URL', 'http://localhost/your-site');

// Common functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Initialize any global variables
$errors = [];
$messages = [];

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}