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
    public function listarProductos(): void {
    try {
        $productos = $this->productoService->listarProductos();
        Response::json($productos, 200);
    } catch (\Exception $e) {
        Response::error($e->getMessage(), 500);
    }
}
}
