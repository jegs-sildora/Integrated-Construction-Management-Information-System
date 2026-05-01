<?php
class Logger {
    public static function log($action, $module, $details, $record_id = null) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO audit_logs (action, module, details, record_id, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$action, $module, $details, $record_id]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    public static function create($module, $details, $record_id = null) { return self::log('CREATE', $module, $details, $record_id); }
    public static function update($module, $details, $record_id = null) { return self::log('UPDATE', $module, $details, $record_id); }
    public static function delete($module, $details, $record_id = null) { return self::log('DELETE', $module, $details, $record_id); }
}
