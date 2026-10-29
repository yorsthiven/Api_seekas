<?php
namespace Controllers;

require_once __DIR__ . '/../services/NegocioService.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/AuthorizationException.php';

use Core\AuthorizationException;
use Core\Response;
use Services\NegocioService;

class NegocioController
{
    private NegocioService $service;

    public function __construct()
    {
        $this->service = new NegocioService();
    }

    public function listarNegocios(int $negocioId): void
    {
        try {
            Response::json($this->service->listarNegocios($negocioId));
        } catch (\Exception $e) {
            $this->reportError($e, 'No fue posible consultar los negocios.');
        }
    }

    public function obtenerNegocioPorId(int $id, int $negocioAutenticado): void
    {
        try {
            Response::json($this->service->obtenerNegocioPorId($id, $negocioAutenticado));
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\OutOfBoundsException $e) {
            Response::error($e->getMessage(), 404);
        } catch (AuthorizationException $e) {
            Response::error($e->getMessage(), 403);
        } catch (\Exception $e) {
            $this->reportError($e, 'No fue posible consultar el negocio.');
        }
    }

    public function crearNegocio(): void
    {
        try {
            $business = $this->service->crearNegocio($this->getJsonBody());
            Response::json([
                'success' => true,
                'message' => 'Negocio creado exitosamente.',
                'data' => $business,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (\DomainException $e) {
            Response::error($e->getMessage(), 409);
        } catch (\Exception $e) {
            $this->reportError($e, 'No fue posible crear el negocio.');
        }
    }

    public function actualizarNegocio(int $id, int $negocioAutenticado): void
    {
        try {
            $business = $this->service->actualizarNegocio(
                $id,
                $this->getJsonBody(),
                $negocioAutenticado
            );
            Response::json([
                'success' => true,
                'message' => 'Negocio actualizado exitosamente.',
                'data' => $business,
            ]);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (\OutOfBoundsException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\DomainException $e) {
            Response::error($e->getMessage(), 409);
        } catch (AuthorizationException $e) {
            Response::error($e->getMessage(), 403);
        } catch (\Exception $e) {
            $this->reportError($e, 'No fue posible actualizar el negocio.');
        }
    }

    public function eliminarNegocio(int $id, int $negocioAutenticado): void
    {
        try {
            $this->service->eliminarNegocio($id, $negocioAutenticado);
            Response::json([
                'success' => true,
                'message' => 'Negocio eliminado exitosamente.',
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
            $this->reportError($e, 'No fue posible eliminar el negocio.');
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

    private function reportError(\Exception $exception, string $message): void
    {
        error_log($exception->getMessage());
        Response::error($message, 500);
    }
}
