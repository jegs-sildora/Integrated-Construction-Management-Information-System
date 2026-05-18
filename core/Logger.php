<?php
/**
 * Logger.php - Audit Trail System
 * 
 * ICMIS - Integrated Construction Management Information System
 * 
 * A centralized logging helper class for recording user actions
 * across all modules without writing SQL queries in each file.
 * 
 * Usage:
 *   require_once BASE_PATH . '/core/Logger.php';
 *   Logger::log('CREATE', 'Project', 'Created new project: Project Alpha', $project_id);
 * 
 * Or with static connection:
 *   Logger::init($conn); // Pass existing mysqli connection
 *   Logger::log('LOGIN', 'Auth', 'User logged in successfully');
 */

class Logger {
    
    /** @var mysqli|null Database connection instance */
    private static ?mysqli $conn = null;
    
    /** @var bool Whether the audit table exists */
    private static bool $tableChecked = false;
    
    /**
     * Initialize the Logger with an existing database connection.
     * Call this once at the start of your script if you want to reuse a connection.
     * 
     * @param mysqli $conn The mysqli connection object
     * @return void
     */
    public static function init(mysqli $conn): void {
        self::$conn = $conn;
    }
    
    /**
     * Get or create a database connection.
     * 
     * @return mysqli|null
     */
    private static function getConnection(): ?mysqli {
        if (self::$conn !== null) {
            return self::$conn;
        }
        
        // Try to create a new connection using config constants
        if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
            try {
                // Use error suppression and check for connection error to avoid mysqli_sql_exception
                self::$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                if (self::$conn->connect_error) {
                    // Fallback to Mock if database.php is available
                    if (class_exists('MockMysqli')) {
                        self::$conn = new MockMysqli();
                    } else {
                        error_log('Logger: Database connection failed - ' . self::$conn->connect_error);
                        self::$conn = null;
                        return null;
                    }
                }
                self::$conn->set_charset("utf8mb4");
                return self::$conn;
            } catch (Exception $e) {
                // Fallback to Mock if database.php is available
                if (class_exists('MockMysqli')) {
                    self::$conn = new MockMysqli();
                    return self::$conn;
                }
                error_log('Logger: Exception during connection - ' . $e->getMessage());
                return null;
            }
        }
        
        return null;
    }
    
    /**
     * Ensure the audit log table exists (runs once per request).
     * 
     * @return bool
     */
    private static function ensureTable(): bool {
        if (self::$tableChecked) {
            return true;
        }
        
        $conn = self::getConnection();
        if (!$conn) {
            return false;
        }
        
        // Check if table exists
        $result = $conn->query("SHOW TABLES LIKE 'icmis_audit_logs'");
        if ($result && $result->num_rows > 0) {
            self::$tableChecked = true;
            return true;
        }
        
        // Create the table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS `icmis_audit_logs` (
            `log_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) DEFAULT NULL,
            `user_name` VARCHAR(100) DEFAULT 'System',
            `action` VARCHAR(50) NOT NULL COMMENT 'CREATE, UPDATE, DELETE, LOGIN, LOGOUT, VIEW, EXPORT, APPROVE, REJECT',
            `module` VARCHAR(50) NOT NULL COMMENT 'Project, Budget, Procurement, Workforce, Auth, Reports, etc.',
            `details` TEXT DEFAULT NULL COMMENT 'Human-readable description of the action',
            `record_id` INT(11) DEFAULT NULL COMMENT 'ID of the affected record (project_id, proposal_id, etc.)',
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `user_agent` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`log_id`),
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_action` (`action`),
            INDEX `idx_module` (`module`),
            INDEX `idx_created_at` (`created_at`),
            CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) 
                REFERENCES `icmis_users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            self::$tableChecked = true;
            return true;
        }
        
        error_log('Logger: Failed to create icmis_audit_logs table - ' . $conn->error);
        return false;
    }
    
    /**
     * Log an action to the audit trail via API.
     * 
     * @param string      $action    The action type (CREATE, UPDATE, DELETE, LOGIN, LOGOUT, VIEW, EXPORT, APPROVE, REJECT)
     * @param string      $module    The module name (Project, Budget, Procurement, Workforce, Auth, Reports)
     * @param string|null $details   Human-readable description of what happened
     * @param int|null    $record_id The ID of the affected record (optional)
     * @param int|null    $user_id   Override user ID (optional, uses session by default)
     * @param string|null $user_name Override user name (optional, uses session by default)
     * @return bool                  True if logged successfully, false otherwise
     */
    public static function log(
        string $action,
        string $module,
        ?string $details = null,
        ?int $record_id = null,
        ?int $user_id = null,
        ?string $user_name = null
    ): bool {
        // Get user info from session if not provided
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        if ($user_id === null) {
            $user_id = $_SESSION['user_id'] ?? null;
        }
        
        if ($user_name === null) {
            $user_name = $_SESSION['user_name'] ?? 'System';
        }
        
        // Get client info
        $ip_address = self::getClientIP();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) 
            ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) 
            : null;
        
        // Prepare data for API
        $data = [
            'user_id' => $user_id,
            'user_name' => $user_name,
            'action' => $action,
            'module' => $module,
            'details' => $details,
            'record_id' => $record_id,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent
        ];
        
        // Send to Auth Microservice via ApiHelper
        if (class_exists('ApiHelper')) {
            $response = ApiHelper::post('auth/audit_logs', $data);
            return isset($response['data']['success']) && $response['data']['success'] === true;
        }
        
        error_log('Logger: ApiHelper not found. Could not log action.');
        return false;
    }
    
    /**
     * Convenience method for logging CREATE actions.
     */
    public static function create(string $module, string $details, ?int $record_id = null): bool {
        return self::log('CREATE', $module, $details, $record_id);
    }
    
    /**
     * Convenience method for logging UPDATE actions.
     */
    public static function update(string $module, string $details, ?int $record_id = null): bool {
        return self::log('UPDATE', $module, $details, $record_id);
    }
    
    /**
     * Convenience method for logging DELETE actions.
     */
    public static function delete(string $module, string $details, ?int $record_id = null): bool {
        return self::log('DELETE', $module, $details, $record_id);
    }
    
    /**
     * Convenience method for logging LOGIN actions.
     */
    public static function login(?int $user_id = null, ?string $user_name = null): bool {
        return self::log('LOGIN', 'Auth', 'User logged in successfully', null, $user_id, $user_name);
    }
    
    /**
     * Convenience method for logging LOGOUT actions.
     */
    public static function logout(): bool {
        return self::log('LOGOUT', 'Auth', 'User logged out');
    }
    
    /**
     * Convenience method for logging APPROVE actions.
     */
    public static function approve(string $module, string $details, ?int $record_id = null): bool {
        return self::log('APPROVE', $module, $details, $record_id);
    }
    
    /**
     * Convenience method for logging REJECT actions.
     */
    public static function reject(string $module, string $details, ?int $record_id = null): bool {
        return self::log('REJECT', $module, $details, $record_id);
    }
    
    /**
     * Convenience method for logging EXPORT actions.
     */
    public static function export(string $module, string $details, ?int $record_id = null): bool {
        return self::log('EXPORT', $module, $details, $record_id);
    }
    
    /**
     * Get the client's IP address.
     * 
     * @return string|null
     */
    private static function getClientIP(): ?string {
        $ip = null;
        
        // Check for proxy headers
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For can contain multiple IPs; take the first one
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                break;
            }
        }
        
        return $ip ? substr($ip, 0, 45) : null;
    }
    
    /**
     * Fetch recent logs (for admin view) via API.
     * 
     * @param int    $limit   Number of records to fetch
     * @param int    $offset  Offset for pagination
     * @param string $module  Filter by module (optional)
     * @param string $action  Filter by action (optional)
     * @param int    $user_id Filter by user ID (optional)
     * @return array
     */
    public static function getLogs(
        int $limit = 50,
        int $offset = 0,
        ?string $module = null,
        ?string $action = null,
        ?int $user_id = null
    ): array {
        if (!class_exists('ApiHelper')) {
            return [];
        }

        $params = [
            'limit' => $limit,
            'offset' => $offset
        ];
        
        if ($module !== null) $params['module'] = $module;
        if ($action !== null) $params['action'] = $action;
        if ($user_id !== null) $params['user'] = $user_id;

        $response = ApiHelper::get('auth/audit_logs?' . http_build_query($params));
        
        return $response['data']['logs'] ?? [];
    }
    
    /**
     * Get total count of logs (for pagination) via API.
     * 
     * @param string|null $module  Filter by module
     * @param string|null $action  Filter by action
     * @param int|null    $user_id Filter by user
     * @return int
     */
    public static function getLogCount(
        ?string $module = null,
        ?string $action = null,
        ?int $user_id = null
    ): int {
        if (!class_exists('ApiHelper')) {
            return 0;
        }

        $params = [];
        if ($module !== null) $params['module'] = $module;
        if ($action !== null) $params['action'] = $action;
        if ($user_id !== null) $params['user'] = $user_id;

        $response = ApiHelper::get('auth/audit_logs?' . http_build_query($params));
        
        return intval($response['data']['total'] ?? 0);
    }
}

