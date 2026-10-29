<?php
namespace Controllers;

require_once __DIR__ . '/../services/UsuarioService.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/AuthorizationException.php';

use Core\AuthorizationException;
use Core\Response;
use Services\UsuarioService;

class UsuarioController
{
    private UsuarioService $service;

    public function __construct()
    {
        $this->service = new UsuarioService();
    }

    public function listarUsuarios(int $negocioId): void
    {
        try {
            $filtroNegocioId = isset($_GET['negocio_id'])
                ? $this->parseId($_GET['negocio_id'])
                : null;
            if ($filtroNegocioId !== null && $filtroNegocioId !== $negocioId) {
                throw new AuthorizationException('No tiene permiso para consultar usuarios de otro negocio.');
            }
            Response::json($this->service->listarUsuarios($negocioId));
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (AuthorizationException $e) {
            Response::error($e->getMessage(), 403);
        } catch (\Exception $e) {
            $this->reportError($e, 'No fue posible consultar los usuarios.');
        }
    }

    public function obtenerUsuario(int $id, int $negocioId): void
    {
        try {
            Response::json($this->service->obtenerUsuario($id, $negocioId));
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\OutOfBoundsException $e) {
            Response::error($e->getMessage(), 404);
        } catch (\Exception $e) {
            $this->reportError($e, 'No fue posible consultar el usuario.');
        }
    }

    public function crearUsuario(?int $negocioId = null): void
    {
        try {
            $user = $this->service->crearUsuario($this->getJsonBody(), $negocioId);
            Response::json([
                'success' => true,
                'message' => 'Usuario creado exitosamente.',
                'data' => $user,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (\DomainException $e) {
            Response::error($e->getMessage(), 409);
        } catch (AuthorizationException $e) {
            Response::error($e->getMessage(), 403);
        } catch (\Exception $e) {
            $this->reportError($e, 'No fue posible crear el usuario.');
        }
    }

    public function actualizarUsuario(int $id, int $negocioId): void
    {
        try {
            $user = $this->service->actualizarUsuario($id, $this->getJsonBody(), $negocioId);
            Response::json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente.',
                'data' => $user,
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
            $this->reportError($e, 'No fue posible actualizar el usuario.');
        }
    }

    public function eliminarUsuario(int $id, int $negocioId): void
    {
        try {
            $this->service->eliminarUsuario($id, $negocioId);
            Response::json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente.',
            ]);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 400);
        } catch (\OutOfBoundsException $e) {
            Response::error($e->getMessage(), 404);
        } catch (AuthorizationException $e) {
            Response::error($e->getMessage(), 403);
        } catch (\Exception $e) {
            $this->reportError($e, 'No fue posible eliminar el usuario.');
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
