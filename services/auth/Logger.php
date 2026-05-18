<?php
/**
 * Auth Service Internal Logger
 * Writes directly to the audit_logs table since this is the Auth service itself.
 */
require_once __DIR__ . '/Database.php';

class Logger {
    public static function log($action, $module, $details, $record_id = null, $user_id = null, $user_name = null) {
        try {
            $db = Database::getConnection();
            
            // Get user info from session if not provided (for internal use)
            if ($user_id === null && session_status() !== PHP_SESSION_NONE) {
                $user_id = $_SESSION['user_id'] ?? null;
                $user_name = $_SESSION['user_name'] ?? 'Auth Service';
            } else if ($user_name === null) {
                $user_name = 'Auth Service';
            }

            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $sql = "INSERT INTO audit_logs (user_id, user_name, action, module, details, record_id, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            return $stmt->execute([$user_id, $user_name, $action, $module, $details, $record_id, $ip_address, $user_agent]);
        } catch (Exception $e) {
            error_log("Logger Error: " . $e->getMessage());
            return false;
        }
    }

    public static function create($module, $details, $record_id = null) { return self::log('CREATE', $module, $details, $record_id); }
    public static function update($module, $details, $record_id = null) { return self::log('UPDATE', $module, $details, $record_id); }
    public static function delete($module, $details, $record_id = null) { return self::log('DELETE', $module, $details, $record_id); }
    public static function login($details, $user_id, $user_name) { return self::log('LOGIN', 'Auth', $details, null, $user_id, $user_name); }
}

