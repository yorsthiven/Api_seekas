<?php
namespace Core;

require_once __DIR__ . '/AuthenticationException.php';
require_once __DIR__ . '/AuthorizationException.php';
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/../config/Secrets.php';

use Config\Secrets;

class Auth
{
    private const TOKEN_TTL = 3600;
    private const TOKEN_ISSUER = 'api-seekas';

    public static function requireUser(): array
    {
        $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        $authorization = $_SERVER['HTTP_AUTHORIZATION']
            ?? $headers['Authorization']
            ?? $headers['authorization']
            ?? '';

        if (!is_string($authorization)
            || preg_match('/^Bearer\s+([A-Za-z0-9._-]+)$/i', trim($authorization), $matches) !== 1) {
            Response::error('Debe iniciar sesión y enviar un token Bearer válido.', 401);
        }

        try {
            return self::verifyToken($matches[1]);
        } catch (AuthenticationException $e) {
            Response::error($e->getMessage(), 401);
        } catch (\RuntimeException $e) {
            error_log($e->getMessage());
            Response::error('La autenticación no está configurada correctamente en el servidor.', 500);
        }
    }

    public static function requireAdmin(): void
    {
        $configuredToken = Secrets::get('api_admin_token');
        if (!is_string($configuredToken) || strlen($configuredToken) < 32) {
            Response::error('La API no tiene configurado un token administrativo seguro.', 503);
        }

        $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        $providedToken = $_SERVER['HTTP_X_ADMIN_TOKEN']
            ?? $headers['X-Admin-Token']
            ?? $headers['x-admin-token']
            ?? '';
        if (!is_string($providedToken) || !hash_equals($configuredToken, $providedToken)) {
            Response::error('Token administrativo inválido.', 401);
        }
    }

    public static function hasAdminTokenHeader(): bool
    {
        $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        $providedToken = $_SERVER['HTTP_X_ADMIN_TOKEN']
            ?? $headers['X-Admin-Token']
            ?? $headers['x-admin-token']
            ?? '';

        return is_string($providedToken) && $providedToken !== '';
    }

    public static function issueToken(int $userId, int $businessId, string $email): array
    {
        if ($userId <= 0 || $businessId <= 0) {
            throw new \InvalidArgumentException('No se pudo crear el token del usuario.');
        }

        $now = time();
        $header = self::base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256',
        ], JSON_THROW_ON_ERROR));
        $payload = self::base64UrlEncode(json_encode([
            'iss' => self::TOKEN_ISSUER,
            'sub' => $userId,
            'negocio_id' => $businessId,
            'email' => $email,
            'iat' => $now,
            'exp' => $now + self::TOKEN_TTL,
        ], JSON_THROW_ON_ERROR));
        $unsignedToken = $header . '.' . $payload;
        $signature = hash_hmac('sha256', $unsignedToken, self::getJwtSecret(), true);

        return [
            'access_token' => $unsignedToken . '.' . self::base64UrlEncode($signature),
            'token_type' => 'Bearer',
            'expires_in' => self::TOKEN_TTL,
        ];
    }

    private static function verifyToken(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new AuthenticationException('El token no es válido.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $decodedHeader = self::base64UrlDecode($encodedHeader);
        $decodedPayload = self::base64UrlDecode($encodedPayload);
        $signature = self::base64UrlDecode($encodedSignature);
        if ($decodedHeader === false || $decodedPayload === false) {
            throw new AuthenticationException('El token no es válido.');
        }
        try {
            $header = json_decode($decodedHeader, true, 512, JSON_THROW_ON_ERROR);
            $claims = json_decode($decodedPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new AuthenticationException('El token no es válido.');
        }
        if (!is_array($header) || ($header['alg'] ?? null) !== 'HS256'
            || ($header['typ'] ?? null) !== 'JWT'
            || !is_array($claims) || !is_string($signature)) {
            throw new AuthenticationException('El token no es válido.');
        }

        $expectedSignature = hash_hmac(
            'sha256',
            $encodedHeader . '.' . $encodedPayload,
            self::getJwtSecret(),
            true
        );
        if (!hash_equals($expectedSignature, $signature)) {
            throw new AuthenticationException('El token no es válido.');
        }

        $now = time();
        if (($claims['iss'] ?? null) !== self::TOKEN_ISSUER
            || !isset($claims['sub'], $claims['negocio_id'], $claims['exp'])
            || !is_int($claims['sub']) || $claims['sub'] <= 0
            || !is_int($claims['negocio_id']) || $claims['negocio_id'] <= 0
            || !is_int($claims['exp']) || $claims['exp'] <= $now) {
            throw new AuthenticationException('El token ha expirado o no es válido.');
        }

        return $claims;
    }

    private static function getJwtSecret(): string
    {
        $secret = Secrets::get('jwt_secret');
        if (!is_string($secret) || strlen($secret) < 32) {
            throw new \RuntimeException('JWT_SECRET debe configurarse con al menos 32 caracteres.');
        }

        return $secret;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string|false
    {
        if (preg_match('/^[A-Za-z0-9_-]*$/', $value) !== 1) {
            return false;
        }

        $padding = (4 - strlen($value) % 4) % 4;
        return base64_decode(strtr($value . str_repeat('=', $padding), '-_', '+/'), true);
    }
}
