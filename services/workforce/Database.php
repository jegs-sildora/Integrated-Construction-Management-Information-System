<?php
class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $dbUrl = getenv('DATABASE_URL') ?: 'pgsql://postgres:postgres@db_workforce:5432/workforce';
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
                die(json_encode(['success' => false, 'message' => "Connection failed: " . $e->getMessage()]));
            }
        }
        return self::$instance;
    }
}

