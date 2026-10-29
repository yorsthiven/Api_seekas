<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Token');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/core/Response.php';
require_once __DIR__ . '/core/Auth.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$segments = explode('/', trim((string) $uri, '/'));
$resource = $segments[0] ?? '';
$pathId = $segments[1] ?? null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$getResourceId = static function () use ($pathId): ?int {
    $value = $_GET['id'] ?? $pathId;
    if ($value === null) {
        return null;
    }

    $id = (is_string($value) || is_int($value))
        ? filter_var($value, FILTER_VALIDATE_INT)
        : false;
    if ($id === false || $id <= 0) {
        \Core\Response::error('El ID debe ser un entero positivo.', 400);
    }

    return $id;
};

$requireId = static function (?int $id, string $resourceName): int {
    if ($id === null) {
        \Core\Response::error("Indique el ID del {$resourceName}.", 400);
    }

    return $id;
};

$rejectCreateId = static function (?int $id): void {
    if ($id !== null) {
        \Core\Response::error('No envíe un ID al crear un registro.', 400);
    }
};

switch ($resource) {
    case 'auth':
        if (($segments[1] ?? '') === 'login' && $method === 'POST') {
            require_once __DIR__ . '/controllers/AuthController.php';
            (new \Controllers\AuthController())->iniciarSesion();
        }
        header('Allow: POST');
        \Core\Response::error('Ruta de autenticación no encontrada o método no permitido.', 404);
        break;

    case 'negocios':
        require_once __DIR__ . '/controllers/NegocioController.php';
        $id = $getResourceId();

        if ($method === 'POST') {
            \Core\Auth::requireAdmin();
            $rejectCreateId($id);
            $controller = new \Controllers\NegocioController();
            $controller->crearNegocio();
            break;
        }

        $identity = \Core\Auth::requireUser();
        $negocioAutenticado = (int) $identity['negocio_id'];
        $controller = new \Controllers\NegocioController();
        if ($method === 'GET') {
            if ($id === null) {
                $controller->listarNegocios($negocioAutenticado);
            } else {
                $controller->obtenerNegocioPorId($id, $negocioAutenticado);
            }
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            $controller->actualizarNegocio(
                $requireId($id, 'negocio'),
                $negocioAutenticado
            );
        } elseif ($method === 'DELETE') {
            $controller->eliminarNegocio(
                $requireId($id, 'negocio'),
                $negocioAutenticado
            );
        } else {
            header('Allow: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            \Core\Response::error('Método no permitido para negocios.', 405);
        }
        break;

    case 'productos':
        require_once __DIR__ . '/controllers/ProductoController.php';
        $id = $getResourceId();
        $identity = \Core\Auth::requireUser();
        $negocioAutenticado = (int) $identity['negocio_id'];
        $controller = new \Controllers\ProductoController();

        if ($method === 'GET') {
            if ($id === null) {
                $controller->listarProductos($negocioAutenticado);
            } else {
                $controller->obtenerProducto($id, $negocioAutenticado);
            }
        } elseif ($method === 'POST') {
            $rejectCreateId($id);
            $controller->crearProducto($negocioAutenticado);
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            $controller->actualizarProducto(
                $requireId($id, 'producto'),
                $negocioAutenticado
            );
        } elseif ($method === 'DELETE') {
            $controller->eliminarProducto(
                $requireId($id, 'producto'),
                $negocioAutenticado
            );
        } else {
            header('Allow: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            \Core\Response::error('Método no permitido para productos.', 405);
        }
        break;

    case 'pedidos':
        require_once __DIR__ . '/controllers/PedidoController.php';
        $id = $getResourceId();
        $identity = \Core\Auth::requireUser();
        $negocioAutenticado = (int) $identity['negocio_id'];
        $controller = new \Controllers\PedidoController();

        if ($method === 'GET') {
            if ($id === null) {
                $controller->listarPedidos($negocioAutenticado);
            } else {
                $controller->obtenerPedido($id, $negocioAutenticado);
            }
        } elseif ($method === 'POST') {
            $rejectCreateId($id);
            $controller->crearPedido($negocioAutenticado);
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            $controller->actualizarPedido(
                $requireId($id, 'pedido'),
                $negocioAutenticado
            );
        } elseif ($method === 'DELETE') {
            $controller->eliminarPedido(
                $requireId($id, 'pedido'),
                $negocioAutenticado
            );
        } else {
            header('Allow: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            \Core\Response::error('Método no permitido para pedidos.', 405);
        }
        break;

    case 'usuarios':
        require_once __DIR__ . '/controllers/UsuarioController.php';
        $id = $getResourceId();
        if ($method === 'POST' && \Core\Auth::hasAdminTokenHeader()) {
            \Core\Auth::requireAdmin();
            $rejectCreateId($id);
            $controller = new \Controllers\UsuarioController();
            $controller->crearUsuario();
            break;
        }
        $identity = \Core\Auth::requireUser();
        $negocioAutenticado = (int) $identity['negocio_id'];
        $controller = new \Controllers\UsuarioController();

        if ($method === 'GET') {
            if ($id === null) {
                $controller->listarUsuarios($negocioAutenticado);
            } else {
                $controller->obtenerUsuario($id, $negocioAutenticado);
            }
        } elseif ($method === 'POST') {
            $rejectCreateId($id);
            $controller->crearUsuario($negocioAutenticado);
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            $controller->actualizarUsuario(
                $requireId($id, 'usuario'),
                $negocioAutenticado
            );
        } elseif ($method === 'DELETE') {
            $controller->eliminarUsuario(
                $requireId($id, 'usuario'),
                $negocioAutenticado
            );
        } else {
            header('Allow: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            \Core\Response::error('Método no permitido para usuarios.', 405);
        }
        break;

    default:
        \Core\Response::error('Recurso no encontrado.', 404);
}
