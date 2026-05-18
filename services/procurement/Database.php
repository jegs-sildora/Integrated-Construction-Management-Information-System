<?php
namespace Procurement;

class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $dbUrl = getenv('DATABASE_URL') ?: 'pgsql://postgres:postgres@db_main:5432/icmis_db';
            $url = parse_url($dbUrl);

            $host = $url['host'] ?? 'db_main';
            $port = $url['port'] ?? 5432;
            $db = isset($url['path']) ? ltrim($url['path'], '/') : 'icmis_db';
            $user = $url['user'] ?? 'postgres';
            $pass = $url['pass'] ?? 'postgres';

            $dsn = "pgsql:host=$host;port=$port;dbname=$db";
            
            try {
                self::$instance = new \PDO($dsn, $user, $pass, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]);
            } catch (\PDOException $e) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
                exit;
            }
        }
        return self::$instance;
    }
}
