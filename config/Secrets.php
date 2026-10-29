<?php
namespace Config;

class Secrets
{
    private static ?array $config = null;

    public static function get(string $key): ?string
    {
        $environmentKeys = [
            'db_host' => 'DB_HOST',
            'db_name' => 'DB_NAME',
            'db_user' => 'DB_USER',
            'db_password' => 'DB_PASSWORD',
            'jwt_secret' => 'JWT_SECRET',
            'api_admin_token' => 'API_ADMIN_TOKEN',
        ];
        $environmentKey = $environmentKeys[$key] ?? null;
        if ($environmentKey !== null && self::getEnvironment() !== 'development') {
            $value = getenv($environmentKey);
            if (is_string($value)) {
                return $value;
            }
        }

        $config = self::load();
        $value = $config[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    public static function getDatabaseConfig(): array
    {
        $keys = ['db_host', 'db_name', 'db_user', 'db_password'];
        $environmentKeys = [
            'db_host' => 'DB_HOST',
            'db_name' => 'DB_NAME',
            'db_user' => 'DB_USER',
            'db_password' => 'DB_PASSWORD',
        ];
        $environmentConfig = [];
        if (self::getEnvironment() !== 'development') {
            foreach ($keys as $key) {
                $value = getenv($environmentKeys[$key]);
                if (!is_string($value)) {
                    $environmentConfig = [];
                    break;
                }
                $environmentConfig[$key] = $value;
            }
        }
        if ($environmentConfig !== []) {
            if ($environmentConfig['db_host'] === ''
                || $environmentConfig['db_name'] === ''
                || $environmentConfig['db_user'] === '') {
                throw new \RuntimeException('La configuración de base de datos está incompleta.');
            }
            $port = getenv('DB_PORT');
            if (is_string($port) && $port !== '') {
                $environmentConfig['db_port'] = self::validatePort($port);
            }
            return $environmentConfig;
        }

        $config = self::load();
        foreach ($keys as $key) {
            if (!isset($config[$key]) && !array_key_exists($key, $config)) {
                throw new \RuntimeException('La configuración de base de datos está incompleta.');
            }
            if (!is_string($config[$key])) {
                throw new \RuntimeException('La configuración de base de datos está incompleta.');
            }
        }
        if ($config['db_host'] === '' || $config['db_name'] === '' || $config['db_user'] === '') {
            throw new \RuntimeException('La configuración de base de datos está incompleta.');
        }

        $databaseConfig = array_intersect_key($config, array_flip($keys));
        if (array_key_exists('db_port', $config)) {
            $databaseConfig['db_port'] = self::validatePort($config['db_port']);
        }

        return $databaseConfig;
    }

    private static function validatePort($port): int
    {
        if (is_string($port) && preg_match('/^\d+$/', $port) === 1) {
            $port = filter_var($port, FILTER_VALIDATE_INT);
        }
        if (!is_int($port) || $port < 1 || $port > 65535) {
            throw new \RuntimeException('El puerto de la base de datos debe estar entre 1 y 65535.');
        }

        return $port;
    }

    private static function load(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $environment = self::getEnvironment();
        if ($environment === 'development') {
            $configFile = __DIR__ . '/database.local.php';
        } else {
            $configFile = dirname(__DIR__, 2) . '/api-secrets.php';
        }

        if (!is_file($configFile)) {
            if ($environment === 'development') {
                throw new \RuntimeException(
                    'Falta config/database.local.php. Cópialo desde database.local.php.example y configúralo para XAMPP.'
                );
            }
            throw new \RuntimeException('Falta el archivo privado de configuración del hosting.');
        }

        $config = require $configFile;
        if (!is_array($config)) {
            throw new \RuntimeException('El archivo privado de configuración no es válido.');
        }

        self::$config = $config;
        return self::$config;
    }

    private static function getEnvironment(): string
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === 'localhost' || str_starts_with($host, 'localhost:')
            || $host === '127.0.0.1' || str_starts_with($host, '127.0.0.1:')
            || $host === '[::1]' || str_starts_with($host, '[::1]:')
            || PHP_SAPI === 'cli-server') {
            return 'development';
        }

        $environment = getenv('APP_ENV');
        if (!is_string($environment) || $environment === '') {
            $environment = $_SERVER['APP_ENV'] ?? '';
        }

        if ($environment === 'development' || $environment === 'production') {
            return $environment;
        }

        return 'production';
    }
}
