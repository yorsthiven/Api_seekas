<?php
namespace Config;

use PDO;
use PDOException;
require_once __DIR__ . '/Secrets.php';

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $config = Secrets::getDatabaseConfig();

            $port = isset($config['db_port']) ? ";port={$config['db_port']}" : '';
            $dsn = "mysql:host={$config['db_host']}{$port};dbname={$config['db_name']};charset=utf8mb4";
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