<?php
/**
 * Workforce Service Internal Logger - Centralized Proxy
 * Redirects service-level logs to the Auth Microservice for global audit trail
 */
class Logger {
    public static function log($action, $module, $details, $record_id = null) {
        $data = [
            'action' => $action,
            'module' => $module,
            'details' => $details,
            'record_id' => $record_id,
            'user_name' => 'Workforce Service'
        ];
        
        // Internal service-to-service call to Auth service
        // Since we are inside the Docker network, we can call the service name directly
        $auth_url = 'http://auth-service/api/v1/audit_logs.php';
        
        $ch = curl_init($auth_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return true; // Non-blocking
    }
    public static function create($module, $details, $record_id = null) { return self::log('CREATE', $module, $details, $record_id); }
    public static function update($module, $details, $record_id = null) { return self::log('UPDATE', $module, $details, $record_id); }
    public static function delete($module, $details, $record_id = null) { return self::log('DELETE', $module, $details, $record_id); }
}
