<?php
namespace Controllers;

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/AuthenticationException.php';

use Core\AuthenticationException;
use Core\Response;
use Services\AuthService;

class AuthController
{
    private AuthService $service;

    public function __construct()
    {
        $this->service = new AuthService();
    }

    public function iniciarSesion(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
                Response::error('El cuerpo debe ser un objeto JSON válido.', 400);
            }

            Response::json($this->service->iniciarSesion($input));
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
        } catch (AuthenticationException $e) {
            Response::error($e->getMessage(), 401);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            Response::error('No fue posible iniciar sesión.', 500);
        }
    }
}
