<?php
namespace Controllers;

require_once __DIR__ . '/../services/ProductoService.php';
require_once __DIR__ . '/../core/Response.php';

use Services\ProductoService;
use Core\Response;

class ProductoController
{
    private ProductoService $productoService;

    public function __construct()
    {
        $this->productoService = new ProductoService();
    }
    public function listarProductos(): void
    {
        try {
            $negocioId = isset($_GET['negocio_id']) ? $this->parseId($_GET['negocio_id']) : null;
            $productos = $this->productoService->listarProductos($negocioId);
            Response::json($productos, 200);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            Response::error('No fue posible consultar los productos.', 500);
        }
    }

    public function obtenerProducto(int $id): void
    {
        try {
            Response::json($this->productoService->obtenerProducto($id), 200);
        } catch (\OutOfBoundsException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            Response::error('No fue posible consultar el producto.', 500);
        }
    }

    public function crearProducto(): void
    {
        try {
            $producto = $this->productoService->crearProducto($this->getJsonBody());
            Response::json([
                'success' => true,
                'message' => 'Producto creado exitosamente.',
                'data' => $producto,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (\DomainException $e) {
            Response::error($e->getMessage(), 409);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            Response::error('No fue posible crear el producto.', 500);
        }
    }

    public function actualizarProducto(int $id): void
    {
        try {
            $producto = $this->productoService->actualizarProducto($id, $this->getJsonBody());
            Response::json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente.',
                'data' => $producto,
            ], 200);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (\OutOfBoundsException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\DomainException $e) {
            Response::error($e->getMessage(), 409);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            Response::error('No fue posible actualizar el producto.', 500);
        }
    }

    public function eliminarProducto(int $id): void
    {
        try {
            $this->productoService->eliminarProducto($id);
            Response::json([
                'success' => true,
                'message' => 'Producto eliminado exitosamente.',
            ], 200);
        } catch (\OutOfBoundsException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            Response::error('No fue posible eliminar el producto.', 500);
        }
    }

    private function getJsonBody(): array
    {
        $rawBody = file_get_contents('php://input');
        $input = json_decode($rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
            throw new \InvalidArgumentException('El cuerpo debe ser un objeto JSON válido.');
        }

        return $input;
    }

    private function parseId($value): int
    {
        if (!is_string($value) && !is_int($value)) {
            throw new \InvalidArgumentException('El ID debe ser un número entero positivo.');
        }

        $id = filter_var($value, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            throw new \InvalidArgumentException('El ID debe ser un número entero positivo.');
        }

        return $id;
    }
}
