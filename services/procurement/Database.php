<?php
/**
 * --- Standardized Database Connector (v1.5 - Production Hardened) ---
 * Standardized connection logic for all microservices.
 */
class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $dbUrl = getenv('DATABASE_URL');
            
            if (!$dbUrl) {
                // Default to local development if env var is missing
                $dbUrl = 'postgresql://postgres:postgres@db_main:5432/icmis_db';
            }

            // Convert postgresql:// to pgsql: for PDO
            $dbUrl = str_replace('postgresql://', 'pgsql://', $dbUrl);
            $parsed = parse_url($dbUrl);

            if (!$parsed || !isset($parsed['host'])) {
                throw new Exception("Invalid DATABASE_URL format.");
            }

            $host = $parsed['host'];
            $port = $parsed['port'] ?? 5432;
            $db = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'icmis_db';
            $user = $parsed['user'] ?? 'postgres';
            $pass = $parsed['pass'] ?? '';

            $dsn = "pgsql:host=$host;port=$port;dbname=$db";
            
            try {
                self::$instance = new \PDO($dsn, $user, $pass, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_TIMEOUT => 5
                ]);
            } catch (\PDOException $e) {
                // Ensure we return a clean error instead of crashing
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Database Connection Failed', 'message' => $e->getMessage()]);
                exit;
            }
        }
        return self::$instance;
    }
}
