<?php
namespace Config;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $config = self::loadConfig();
            if (!isset($config['db_host'], $config['db_name'], $config['db_user'], $config['db_password'])) {
                throw new \RuntimeException('La configuración de base de datos está incompleta.');
            }

            $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $config['db_user'], $config['db_password'], $options);
            } catch (PDOException $e) {
                error_log($e->getMessage());
                throw new \RuntimeException('No fue posible conectar con la base de datos.', 0, $e);
            }
        }
        return self::$instance;
    }

    private static function loadConfig(): array
    {
        $environmentConfig = [
            'db_host' => getenv('DB_HOST'),
            'db_name' => getenv('DB_NAME'),
            'db_user' => getenv('DB_USER'),
            'db_password' => getenv('DB_PASSWORD'),
        ];
        if (count(array_filter($environmentConfig, 'is_string')) === count($environmentConfig)) {
            return $environmentConfig;
        }

        $configFiles = [
            __DIR__ . '/database.local.php',
            dirname(__DIR__, 2) . '/api-secrets.php',
        ];
        foreach ($configFiles as $configFile) {
            if (!is_file($configFile)) {
                continue;
            }
            $config = require $configFile;
            if (!is_array($config)) {
                throw new \RuntimeException('El archivo privado de configuración no es válido.');
            }
            if (isset($config['db_host'], $config['db_name'], $config['db_user'], $config['db_password'])) {
                return $config;
            }
        }

        throw new \RuntimeException('Falta configurar la conexión privada de base de datos.');
    }
}