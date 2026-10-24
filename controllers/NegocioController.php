<?php
namespace Controllers;

require_once __DIR__ . '/../services/NegocioService.php';
require_once __DIR__ . '/../core/Response.php';

use Services\NegocioService; // Ajusta el namespace si tu servicio lo usa
use Core\Response;

class NegocioController {
    private NegocioService $service;

    public function __construct() {
        $this->service = new NegocioService();
    }

    public function listarNegocios(): void {
        try {
            $negocios = $this->service->listarNegocios();
            Response::json($negocios, 200);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function obtenerNegocioPorId(int $id): void {
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
}