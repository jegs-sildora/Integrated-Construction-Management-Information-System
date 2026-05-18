<?php
namespace Procurement;

use PDO;
use PDOException;

class Database {
    private $host = 'db_procurement';
    private $port = '5432';
    private $db_name = 'procurement';
    private $username = 'postgres';
    private $password = 'postgres';
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(3, 2);
            $this->conn->setAttribute(19, 2);
        } catch(PDOException $exception) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Connection error: " . $exception->getMessage()]);
            exit;
        }

        return $this->conn;
    }
}

