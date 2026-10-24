<?php
// index.php (Punto de entrada principal)

header("Content-Type: application/json; charset=UTF-8");

// Obtener la ruta solicitada y el método HTTP
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// $segments[0] podría ser el recurso (ej. 'negocios' o 'productos')
$segments = explode('/', trim($uri, '/'));

$resource = isset($segments[0]) ? $segments[0] : '';
$method = $_SERVER['REQUEST_METHOD'];
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
            // } elseif ($method === 'POST') {
            //     $controller->crearNegocio();
            // } elseif ($method === 'PUT') {
            //     $controller->actualizarNegocio($id);
            // } elseif ($method === 'DELETE') {
            //     $controller->eliminarNegocio($id);
            //
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido"]);
        }
        break;

    case 'productos':
        require_once __DIR__ . '/controllers/ProductoController.php';
        $controller = new ProductoController();

        if ($method === 'GET') {
            $controller->listarProductos();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido"]);
        }
        break;


    default:
        http_response_code(404);
        echo json_encode(["error" => "Recurso no encontrado"]);
        break;
}
?>