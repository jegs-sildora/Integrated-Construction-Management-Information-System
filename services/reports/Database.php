<?php
/**
 * --- Standardized Database Connector (v1.5 - Absolute Stability) ---
 */
class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $dbUrl = getenv('DATABASE_URL');
            
            if (!$dbUrl) {
                // Fallback to local Docker Compose default
                $dbUrl = 'postgresql://postgres:postgres@db_main:5432/icmis_db';
            }

            // --- Absolute Stability Fix ---
            // Render and most Postgres providers use standard URI formats.
            // Converting postgresql:// to pgsql: for PHP's PDO while keeping the rest.
            $pdoDsn = str_replace('postgresql://', 'pgsql:', $dbUrl);
            
            // Extract credentials if present in URI
            $user = null;
            $pass = null;
            
            if (preg_match('/pgsql:\/\/([^:]+):([^@]+)@/', $dbUrl, $matches)) {
                $user = $matches[1];
                $pass = $matches[2];
                // Strip credentials from DSN for PDO compatibility
                $pdoDsn = str_replace($matches[1] . ':' . $matches[2] . '@', '', $pdoDsn);
            }

            try {
                self::$instance = new \PDO($pdoDsn, $user, $pass, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::ATTR_TIMEOUT => 5
                ]);
            } catch (\PDOException $e) {
                // Throwing ensures the API catch block handles it cleanly in JSON
                throw new Exception("Database Connection Failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
