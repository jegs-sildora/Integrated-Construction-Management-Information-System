<?php
namespace Budget;

use PDO;
use PDOException;

class Database {
    private $host = 'db_budget';
    private $port = '5432';
    private $db_name = 'budget';
    private $username = 'postgres'; // Assuming default or common dev username
    private $password = 'postgres'; // Assuming default or common dev password
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERR_MODE, PDO::ERR_MODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => "Connection error: " . $exception->getMessage()]);
            exit;
        }

        return $this->conn;
    }
}
