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
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } catch (PDOException $e) {
                die(json_encode(['success' => false, 'message' => "Connection failed: " . $e->getMessage()]));
            }
        }
        return self::$instance;
    }
}
