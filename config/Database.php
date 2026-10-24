<?php
namespace Config;

use PDO;
use PDOException;
use Exception;

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = '5.9.79.107';
            $db   = 'prowebco_prueba1';
            $user = 'prowebco_usuario_prueba1';
            $pass = 'Contra_user_1';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
                echo json_encode(["error" => "Error de conexión", "detalle" => $e->getMessage()]);
                exit;
            }
        }
        return self::$instance;
    }
}