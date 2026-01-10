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
        if (self::$conn !== null && self::$conn->ping()) {
            return self::$conn;
        }
        
        // Try to create a new connection using config constants
        if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
            self::$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if (self::$conn->connect_error) {
                error_log('Logger: Database connection failed - ' . self::$conn->connect_error);
                self::$conn = null;
                return null;
            }
            self::$conn->set_charset("utf8mb4");
            return self::$conn;
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
     * Log an action to the audit trail.
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
        // Ensure we have a valid table
        if (!self::ensureTable()) {
            return false;
        }
        
        $conn = self::getConnection();
        if (!$conn) {
            return false;
        }
        
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
        
        // Prepare and execute the insert
        $stmt = $conn->prepare(
            "INSERT INTO icmis_audit_logs 
             (user_id, user_name, action, module, details, record_id, ip_address, user_agent, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        
        if (!$stmt) {
            error_log('Logger: Prepare failed - ' . $conn->error);
            return false;
        }
        
        $stmt->bind_param(
            "issssiis",
            $user_id,
            $user_name,
            $action,
            $module,
            $details,
            $record_id,
            $ip_address,
            $user_agent
        );
        
        $result = $stmt->execute();
        
        if (!$result) {
            error_log('Logger: Execute failed - ' . $stmt->error);
        }
        
        $stmt->close();
        return $result;
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
     * Fetch recent logs (for admin view).
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
        if (!self::ensureTable()) {
            return [];
        }
        
        $conn = self::getConnection();
        if (!$conn) {
            return [];
        }
        
        $sql = "SELECT l.*, u.email as user_email 
                FROM icmis_audit_logs l
                LEFT JOIN icmis_users u ON l.user_id = u.user_id
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if ($module !== null) {
            $sql .= " AND l.module = ?";
            $params[] = $module;
            $types .= 's';
        }
        
        if ($action !== null) {
            $sql .= " AND l.action = ?";
            $params[] = $action;
            $types .= 's';
        }
        
        if ($user_id !== null) {
            $sql .= " AND l.user_id = ?";
            $params[] = $user_id;
            $types .= 'i';
        }
        
        $sql .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        
        $stmt->close();
        return $logs;
    }
    
    /**
     * Get total count of logs (for pagination).
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
        if (!self::ensureTable()) {
            return 0;
        }
        
        $conn = self::getConnection();
        if (!$conn) {
            return 0;
        }
        
        $sql = "SELECT COUNT(*) as total FROM icmis_audit_logs WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if ($module !== null) {
            $sql .= " AND module = ?";
            $params[] = $module;
            $types .= 's';
        }
        
        if ($action !== null) {
            $sql .= " AND action = ?";
            $params[] = $action;
            $types .= 's';
        }
        
        if ($user_id !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $user_id;
            $types .= 'i';
        }
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return intval($row['total'] ?? 0);
    }
}
