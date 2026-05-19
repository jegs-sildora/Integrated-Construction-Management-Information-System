<?php
class JwtUtils {
    private static $secret = '7424cc33bed68689ad81c6bd3a7cafc4c5572a3d7ef30ce6c0875033944d99d1';

    public static function generate(array $payload): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['iat'] = time();
        $payload['exp'] = time() + (60 * 60 * 24);
        $payloadJson = json_encode($payload);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payloadJson);

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getSecret(), true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function validate(string $token): array|false {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;

        [$header, $payload, $signature] = $parts;
        $validSignature = self::base64UrlEncode(hash_hmac('sha256', $header . "." . $payload, self::getSecret(), true));

        if ($signature !== $validSignature) return false;

        $payloadData = json_decode(self::base64UrlDecode($payload), true);
        if (!$payloadData || ($payloadData['exp'] ?? 0) < time()) return false;

        return $payloadData;
    }

    private static function base64UrlEncode(string $data): string {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64UrlDecode(string $data): string {
        $remainder = strlen($data) % 4;
        if ($remainder) { $data .= str_repeat('=', 4 - $remainder); }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }

    private static function getSecret(): string {
        return (string)(getenv('JWT_SECRET') ?: self::$secret);
    }
}
