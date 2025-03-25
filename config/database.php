<?php
class Database {
    private $host = "mysql-growease.alwaysdata.net";
    private $db_name = "growease_db";
    private $username = "growease";  // Default XAMPP username
    private $password = "@Mynewpassword123";      // Empty password as specified
    public $conn;

    // Get the database connection
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
            
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }

        return $this->conn;
    }
} 