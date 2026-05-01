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
