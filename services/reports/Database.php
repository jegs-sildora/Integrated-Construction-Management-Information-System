<?php
/**
 * --- Standardized Database Connector (v1.6 - MATHEMATICALLY CORRECT) ---
 */
class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $dbUrl = getenv('DATABASE_URL');
            
            if (!$dbUrl) {
                // Fallback to local Docker default
                $dbUrl = 'postgresql://postgres:postgres@db_main:5432/icmis_db';
            }

            // 1. Clean the URL (Render sometimes adds whitespace)
            $dbUrl = trim($dbUrl);

            // 2. Parse URI robustly
            $components = parse_url($dbUrl);
            if (!$components || !isset($components['host'])) {
                throw new Exception("Malformed DATABASE_URL. Cannot parse host.");
            }

            $host = $components['host'];
            $port = $components['port'] ?? 5432;
            $db   = isset($components['path']) ? ltrim($components['path'], '/') : 'icmis_db';
            $user = $components['user'] ?? 'postgres';
            $pass = $components['pass'] ?? '';

            // 3. Construct clean PDO DSN
            $dsn = "pgsql:host=$host;port=$port;dbname=$db";
            
            try {
                self::$instance = new \PDO($dsn, $user, $pass, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::ATTR_TIMEOUT => 5
                ]);
            } catch (\PDOException $e) {
                throw new Exception("Database Connection Failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
