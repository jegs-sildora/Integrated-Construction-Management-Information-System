<?php
/**
 * Internal Service Logger - Centralized Proxy
 * Redirects service-level logs to the Auth Microservice for global audit trail
 */
class Logger {
    public static function log($action, $module, $details, $record_id = null) {
        $user_id = $_SERVER['HTTP_X_USER_ID'] ?? null;
        $user_name = $_SERVER['HTTP_X_USER_NAME'] ?? 'System Service';
        
        $data = [
            'action' => $action,
            'module' => $module,
            'details' => $details,
            'record_id' => $record_id,
            'user_id' => $user_id,
            'user_name' => $user_name,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        $auth_url = (getenv('AUTH_SERVICE_URL') ?: 'http://auth-service') . '/api/v1/audit_logs.php';
        
        $ch = curl_init($auth_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        
        $response = curl_exec($ch);
        
        return true; 
    }
    public static function create($module, $details, $record_id = null) { return self::log('CREATE', $module, $details, $record_id); }
    public static function update($module, $details, $record_id = null) { return self::log('UPDATE', $module, $details, $record_id); }
    public static function delete($module, $details, $record_id = null) { return self::log('DELETE', $module, $details, $record_id); }
}
