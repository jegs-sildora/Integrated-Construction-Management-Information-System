<?php
/**
 * Standardized Database Connector (v1.4 - Production Hardened)
 */
class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $dbUrl = getenv('DATABASE_URL');
            
            if (!$dbUrl) {
                throw new Exception("Environment variable DATABASE_URL is missing.");
            }

            $url = parse_url($dbUrl);
            if (!$url || !isset($url['host'])) {
                throw new Exception("Invalid DATABASE_URL format.");
            }

            $host = $url['host'];
            $port = $url['port'] ?? 5432;
            $db = isset($url['path']) ? ltrim($url['path'], '/') : 'icmis_db';
            $user = $url['user'] ?? 'postgres';
            $pass = $url['pass'] ?? '';

            $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=no-verify";
            
            self::$instance = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$instance;
    }
}
