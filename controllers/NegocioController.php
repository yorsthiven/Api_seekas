<?php
require_once __DIR__ . '/../services/NegocioService.php';
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
            echo json_encode($negocios);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function obtenerNegocioPorId(int $id): void
    {
        try {
            $negocio = $this->service->obtenerNegocioPorId($id);
            echo json_encode($negocio);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }
}