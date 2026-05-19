<?php
/**
 * --- Standardized Database Connector (v1.7 - INDUSTRIAL GRADE) ---
 * Added version tracking to verify deployment state.
 */
class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $dbUrl = getenv('DATABASE_URL') ?: 'postgresql://postgres:postgres@db_main:5432/icmis_db';
            $dbUrl = trim($dbUrl);

            // 1. Robust URI Parsing (v1.7 Logic)
            $parsed = parse_url($dbUrl);
            if (!$parsed || !isset($parsed['host'])) {
                throw new Exception("[V1.7-LATEST] Malformed DATABASE_URL.");
            }

            $host = $parsed['host'];
            $port = $parsed['port'] ?? 5432;
            $db   = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'icmis_db';
            $user = $parsed['user'] ?? 'postgres';
            $pass = $parsed['pass'] ?? '';

            // 2. Strict DSN Construction (Explicit key=value for PDO stability)
            $dsn = "pgsql:host=$host;port=$port;dbname=$db";
            
            try {
                self::$instance = new \PDO($dsn, $user, $pass, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } catch (\PDOException $e) {
                // The version tag helps us verify if the latest code is live
                throw new Exception("[V1.7-LATEST] Connection Failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
