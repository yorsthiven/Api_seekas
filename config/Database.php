<?php
// config/Database.php

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            // Carga las credenciales y la variable $pdo desde conexion.php
            $conexionPath = __DIR__ . '/../conexion.php';
            if (file_exists($conexionPath)) {
                require_once $conexionPath;
            } else {
                throw new Exception("Archivo conexion.php no encontrado.");
            }
            
            self::$instance = $pdo;
        }
        return self::$instance;
    }
}