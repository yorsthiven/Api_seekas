<?php
namespace Config;

use PDO;
use PDOException;
use Exception;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $configFile = __DIR__ . '/database.local.php';

            if (!file_exists($configFile)) {
                throw new Exception("El archivo de configuración de base de datos no existe.");
            }

            $config = require $configFile;

            $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $config['user'], $config['pass'], $options);
            } catch (PDOException $e) {
                \Core\Response::error("Error de conexión: " . $e->getMessage(), 500);
            }
        }
        return self::$instance;
    }
}