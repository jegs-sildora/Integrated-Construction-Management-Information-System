<?php
/**
 * --- Standardized Database Connector (v1.8 - VERIFIED FINAL) ---
 */
class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $dbUrl = getenv('DATABASE_URL') ?: 'postgresql://postgres:postgres@db_main:5432/icmis_db';
            $dbUrl = trim($dbUrl);

            // 1. Surgical URI Parsing (Zero-Regex Logic)
            $parsed = parse_url($dbUrl);
            if (!$parsed || !isset($parsed['host'])) {
                throw new Exception("[V1.8-FINAL] Malformed DATABASE_URL: Parse failed.");
            }

            $host = $parsed['host'];
            $port = $parsed['port'] ?? 5432;
            $db   = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'icmis_db';
            $user = $parsed['user'] ?? 'postgres';
            $pass = $parsed['pass'] ?? '';

            // 2. Strict DSN construction (PDO requires host= and dbname=)
            $dsn = "pgsql:host=$host;port=$port;dbname=$db";
            
            try {
                // We pass user and pass as separate arguments, NOT inside the DSN
                self::$instance = new \PDO($dsn, $user, $pass, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } catch (\PDOException $e) {
                // This tag PROVES you are running the latest code
                throw new Exception("[V1.8-FINAL] Connection Failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
