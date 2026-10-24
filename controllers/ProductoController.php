<?php
require_once __DIR__ . '/../services/ProductoService.php';

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
            $productos = $this->productoService->listarProductos();
            echo json_encode($productos);
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }

    }
}
