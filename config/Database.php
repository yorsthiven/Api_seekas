<?php
namespace Config;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $configFile = dirname(__DIR__, 3) . '/api-secrets.php';

            if (!file_exists($configFile)) {
                throw new \RuntimeException('No está configurado el archivo privado de la API.');
            }

            $config = require $configFile;
            if (!is_array($config)
                || !isset($config['db_host'], $config['db_name'], $config['db_user'], $config['db_password'])) {
                throw new \RuntimeException('La configuración privada de base de datos está incompleta.');
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
}