<?php
/**
 * Logger.php - Audit Trail Proxy System
 * 
 * ICMIS - Integrated Construction Management Information System
 * 
 * A centralized logging helper class for recording user actions.
 * In this microservices architecture, it proxies requests to the 
 * Auth Microservice via the API Gateway.
 */

class Logger {
    
    /**
     * Log an action to the audit trail via API.
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
    
    public static function create(string $module, string $details, ?int $record_id = null): bool {
        return self::log('CREATE', $module, $details, $record_id);
    }
    
    public static function update(string $module, string $details, ?int $record_id = null): bool {
        return self::log('UPDATE', $module, $details, $record_id);
    }
    
    public static function delete(string $module, string $details, ?int $record_id = null): bool {
        return self::log('DELETE', $module, $details, $record_id);
    }
    
    public static function login(?int $user_id = null, ?string $user_name = null): bool {
        return self::log('LOGIN', 'Auth', 'User logged in successfully', null, $user_id, $user_name);
    }
    
    public static function logout(): bool {
        return self::log('LOGOUT', 'Auth', 'User logged out');
    }
    
    public static function approve(string $module, string $details, ?int $record_id = null): bool {
        return self::log('APPROVE', $module, $details, $record_id);
    }
    
    public static function reject(string $module, string $details, ?int $record_id = null): bool {
        return self::log('REJECT', $module, $details, $record_id);
    }
    
    public static function export(string $module, string $details, ?int $record_id = null): bool {
        return self::log('EXPORT', $module, $details, $record_id);
    }
    
    private static function getClientIP(): ?string {
        $ip = null;
        $headers = ['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','HTTP_X_FORWARDED','HTTP_X_CLUSTER_CLIENT_IP','HTTP_FORWARDED_FOR','HTTP_FORWARDED','REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                break;
            }
        }
        return $ip ? substr($ip, 0, 45) : null;
    }
}
