<?php
// core/Response.php

class Response {
    // Método para enviar respuestas exitosas en formato JSON
    public static function json(mixed $data, int $statusCode = 200): void {
        // Encabezados obligatorios para permitir peticiones desde cualquier origen (CORS) y asegurar UTF-8
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json; charset=UTF-8");

        // Manejo preliminar para peticiones de tipo OPTIONS (preflight de JavaScript)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }

    // Método estandarizado para enviar errores en formato JSON
    public static function error(string $message, int $statusCode = 400): void {
        self::json(['success' => false, 'error' => $message], $statusCode);
    }
}