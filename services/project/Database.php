<?php
class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $dbUrl = getenv('DATABASE_URL') ?: 'pgsql://postgres:postgres@db_project:5432/project';
            $url = parse_url($dbUrl);

            $host = $url['host'];
            $port = $url['port'] ?? 5432;
            $db = ltrim($url['path'], '/');
            $user = $url['user'];
            $pass = $url['pass'];

            $dsn = "pgsql:host=$host;port=$port;dbname=$db";
            
            try {
                self::$instance = new \PDO($dsn, $user, $pass, [
                    3 => 2,
                    19 => 2
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
                exit;
            }
        }
        return self::$instance;
    }
}

