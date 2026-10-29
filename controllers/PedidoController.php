<?php
namespace Controllers;

require_once __DIR__ . '/../services/PedidoService.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/AuthorizationException.php';

use Core\AuthorizationException;
use Core\Response;
use Services\PedidoService;

class PedidoController
{
    private PedidoService $service;

    public function __construct()
    {
        $this->service = new PedidoService();
    }

    public function listarPedidos(int $negocioId): void
    {
        try {
            $filtroNegocioId = isset($_GET['negocio_id'])
                ? $this->parseId($_GET['negocio_id'])
                : null;
            Response::json($this->service->listarPedidos($negocioId, $filtroNegocioId));
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (AuthorizationException $e) {
            Response::error($e->getMessage(), 403);
        } catch (\Exception $e) {
            $this->reportError($e, 'No fue posible consultar los pedidos.');
        }
    }

    public function obtenerPedido(int $id, int $negocioId): void
    {
        try {
            Response::json($this->service->obtenerPedido($id, $negocioId));
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\OutOfBoundsException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            $this->reportError($e, 'No fue posible consultar el pedido.');
        }
    }

    public function crearPedido(int $negocioId): void
    {
        try {
            $order = $this->service->crearPedido($this->getJsonBody(), $negocioId);
            Response::json([
                'success' => true,
                'message' => 'Pedido creado exitosamente.',
                'data' => $order,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (AuthorizationException $e) {
            Response::error($e->getMessage(), 403);
        } catch (\Exception $e) {
            $this->reportError($e, 'No fue posible crear el pedido.');
        }
    }

    public function actualizarPedido(int $id, int $negocioId): void
    {
        try {
            $order = $this->service->actualizarPedido($id, $this->getJsonBody(), $negocioId);
            Response::json([
                'success' => true,
                'message' => 'Pedido actualizado exitosamente.',
                'data' => $order,
            ]);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (\OutOfBoundsException $e) {
            Response::error($e->getMessage(), 404);
        } catch (AuthorizationException $e) {
            Response::error($e->getMessage(), 403);
        } catch (\Exception $e) {
            $this->reportError($e, 'No fue posible actualizar el pedido.');
        }
    }

    public function eliminarPedido(int $id, int $negocioId): void
    {
        try {
            $this->service->eliminarPedido($id, $negocioId);
            Response::json([
                'success' => true,
                'message' => 'Pedido eliminado exitosamente.',
            ]);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\OutOfBoundsException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\DomainException $e) {
            Response::error($e->getMessage(), 409);
        } catch (AuthorizationException $e) {
            Response::error($e->getMessage(), 403);
        } catch (\Exception $e) {
            $this->reportError($e, 'No fue posible eliminar el pedido.');
        }
    }

    private function getJsonBody(): array
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
            throw new \InvalidArgumentException('El cuerpo debe ser un objeto JSON válido.');
        }

        return $input;
    }

    private function parseId($value): int
    {
        if (!is_string($value) && !is_int($value)) {
            throw new \InvalidArgumentException('El ID debe ser un entero positivo.');
        }
        $id = filter_var($value, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            throw new \InvalidArgumentException('El ID debe ser un entero positivo.');
        }

        return $id;
    }

    private function reportError(\Exception $exception, string $message): void
    {
        error_log($exception->getMessage());
        Response::error($message, 500);
    }
}
