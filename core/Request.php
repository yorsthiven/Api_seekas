<?php
// core/Request.php

class Request {
    // Obtiene parámetros query de la URL ($_GET)
    public static function getQuery(string $key, mixed $default = null): mixed {
        return $_GET[$key] ?? $default;
    }

    // Obtiene el cuerpo de la petición cuando se envía un JSON por POST
    public static function getBody(): array {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    // Obtiene el método de la petición (GET, POST, etc.)
    public static function getMethod(): string {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }
}