<?php
use Controllers\NegocioController;
use Controllers\ProductoController;

// index.php (Punto de entrada principal)

// 1. Cabeceras CORS obligatorias
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Si es una petición preflight de CORS (OPTIONS), responder y salir de inmediato
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json; charset=UTF-8");

// Obtener la ruta solicitada y el método HTTP
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// $segments[0] podría ser el recurso (ej. 'negocios' o 'productos')
$segments = explode('/', trim($uri, '/'));

$resource = isset($segments[0]) ? $segments[0] : '';
$method = $_SERVER['REQUEST_METHOD'];
$pathId = $segments[1] ?? null;
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

switch ($resource) {
    case 'negocios':
        require_once __DIR__ . '/controllers/NegocioController.php';
        $controller = new NegocioController();

        if ($method === 'GET') {
            if ($id > 0) {
                $controller->obtenerNegocioPorId($id);
            } else {
                $controller->listarNegocios();
            }

        } elseif ($method === 'PUT') {
            $controller->actualizarConfiguracion();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido"]);
        }
        break;
    case 'productos':
        require_once __DIR__ . '/controllers/ProductoController.php';
        $controller = new ProductoController();
        $idValue = $_GET['id'] ?? $pathId;
        $productId = null;
        if ($idValue !== null) {
            $productId = (is_string($idValue) || is_int($idValue))
                ? filter_var($idValue, FILTER_VALIDATE_INT)
                : false;
            if ($productId === false || $productId <= 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'El ID del producto debe ser un entero positivo.',
                ], JSON_UNESCAPED_UNICODE);
                break;
            }
        }

        if ($method === 'GET') {
            if ($productId !== null) {
                $controller->obtenerProducto($productId);
            } else {
                $controller->listarProductos();
            }
        } elseif ($method === 'POST') {
            if ($productId !== null) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'No envíe un ID al crear un producto.',
                ], JSON_UNESCAPED_UNICODE);
            } else {
                $controller->crearProducto();
            }
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            if ($productId === null) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Indique el ID del producto que desea actualizar.',
                ], JSON_UNESCAPED_UNICODE);
            } else {
                $controller->actualizarProducto($productId);
            }
        } elseif ($method === 'DELETE') {
            if ($productId === null) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Indique el ID del producto que desea eliminar.',
                ], JSON_UNESCAPED_UNICODE);
            } else {
                $controller->eliminarProducto($productId);
            }
        } else {
            header('Allow: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Método no permitido para productos.',
            ], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "Recurso no encontrado"]);
        break;
}
?>