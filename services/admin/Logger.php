<?php
/**
 * Internal Service Logger - Centralized Proxy
 * Redirects service-level logs to the Auth Microservice for global audit trail
 */
class Logger {
    public static function log($action, $module, $details, $record_id = null) {
        try {
            $db = \Database::getConnection();
            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, user_name, action, module, details, record_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SERVER['HTTP_X_USER_ID'] ?? null,
                $_SERVER['HTTP_X_USER_NAME'] ?? 'System Service',
                $action,
                $module,
                $details,
                $record_id,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            return true;
        } catch (\Exception $e) {
            // Silently fail or log to error_log to prevent crashing the main flow
            error_log("Audit Log Failure: " . $e->getMessage());
            return false;
        }
    }
    public static function create($module, $details, $record_id = null) { return self::log('CREATE', $module, $details, $record_id); }
    public static function update($module, $details, $record_id = null) { return self::log('UPDATE', $module, $details, $record_id); }
    public static function delete($module, $details, $record_id = null) { return self::log('DELETE', $module, $details, $record_id); }
}
