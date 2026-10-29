<?php
namespace Controllers;

require_once __DIR__ . '/../services/NegocioService.php';
require_once __DIR__ . '/../core/Response.php';

use Services\NegocioService; // Ajusta el namespace si tu servicio lo usa
use Core\Response;

class NegocioController
{
    private NegocioService $service;

    public function __construct()
    {
        $this->service = new NegocioService();
    }

    public function listarNegocios(): void
    {
        try {
            $negocios = $this->service->listarNegocios();
            Response::json($negocios, 200);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function obtenerNegocioPorId(int $id): void
    {
        try {
            $negocio = $this->service->obtenerNegocioPorId($id);
            if (!$negocio) {
                Response::error("Negocio no encontrado", 404);
            }
            Response::json($negocio, 200);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function actualizarConfiguracion(): void {
        try {
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if ($id <= 0) {
                Response::error("ID de negocio inválido", 400);
            }

            // Obtener los datos enviados en formato JSON (desde Postman o Angular)
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input) {
                Response::error("No se recibieron datos para actualizar", 400);
            }

            $actualizado = $this->service->actualizarConfiguracion($id, $input);

            if ($actualizado) {
                Response::json(["message" => "Configuración del negocio actualizada exitosamente"], 200);
            } else {
                Response::error("No se pudo actualizar el negocio", 400);
            }

        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }
}