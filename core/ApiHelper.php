<?php
/**
 * ApiHelper.php - Microservices Communication Helper
 * 
 * Provides a centralized way for the monolithic frontend to communicate
 * with the microservices via the API Gateway.
 */

class ApiHelper {
    
    /**
     * Send a request to the API Gateway.
     * 
     * @param string $endpoint The API endpoint (e.g., 'project/projects')
     * @param string $method The HTTP method (GET, POST, PUT, DELETE)
     * @param array|null $data The data to send (for POST/PUT)
     * @return array The response with 'status' and 'data'
     */
    public static function call($endpoint, $method = 'GET', $data = null) {
        if (!defined('GATEWAY_URL')) {
            throw new Exception('GATEWAY_URL is not defined in config.php');
        }

        $url = GATEWAY_URL . ltrim($endpoint, '/');
        $ch = curl_init($url);
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        // Inject JWT token from session if available
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        if (isset($_SESSION['jwt_token'])) {
            $headers[] = 'Authorization: Bearer ' . $_SESSION['jwt_token'];
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        
        if ($data !== null && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        if ($error) {
            return [
                'status' => 500,
                'data' => [
                    'success' => false,
                    'error' => 'Gateway Connection Error: ' . $error,
                    'attempted_url' => $url
                ]
            ];
        }

        $decoded = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => $httpCode,
                'data' => [
                    'success' => false,
                    'error' => 'Invalid JSON response from service',
                    'raw_response' => substr($response, 0, 1000), // Limit size
                    'json_error' => json_last_error_msg()
                ]
            ];
        }
        
        return [
            'status' => $httpCode,
            'data' => $decoded
        ];
    }
    
    public static function get($endpoint) {
        return self::call($endpoint, 'GET');
    }
    
    public static function post($endpoint, $data) {
        return self::call($endpoint, 'POST', $data);
    }

    public static function put($endpoint, $data) {
        return self::call($endpoint, 'PUT', $data);
    }

    public static function delete($endpoint) {
        return self::call($endpoint, 'DELETE');
    }
}
